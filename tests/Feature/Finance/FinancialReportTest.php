<?php

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFFinancialReportType;
use App\Enums\PRFProcessingStatus;
use App\Enums\PRFResponsibleDesk;
use App\Exports\Finance\CashbookExport;
use App\Exports\Finance\MonthlyAccountabilityExport;
use App\Http\Controllers\Finance\DownloadFinancialReportController;
use App\Jobs\FinancialReport\CreateJob as CreateFinancialReportJob;
use App\Jobs\LedgerEntry\CreateJob as CreateLedgerEntryJob;
use App\Jobs\Refund\CreateJob as CreateRefundJob;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\AppSetting;
use App\Models\FinancialReport;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\FinancialReport\FinancialReportReadyNotification;
use App\Services\Finance\ChartOfAccounts;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

function workbook(object $export): Spreadsheet
{
    $path = tempnam(sys_get_temp_dir(), 'prf') . '.xlsx';
    file_put_contents($path, Excel::raw($export, ExcelFormat::XLSX));

    return IOFactory::load($path);
}

function postLine(string $account, string $category, int $amount, string $date = '2026-03-10'): void
{
    $chart = app(ChartOfAccounts::class);

    CreateLedgerEntryJob::dispatchSync([
        'financial_account_ulid' => $chart->account(PRFFinancialAccountType::from((int) $account))->ulid,
        'ledger_category_ulid' => $chart->category($category)->ulid,
        'amount' => $amount,
        'transacted_on' => $date,
        'counterparty' => 'Someone',
    ]);
}

describe('cashbook workbook', function () {
    beforeEach(function () {
        postLine((string) PRFFinancialAccountType::PAYBILL->value, 'income.member_contribution', 5_000, '2025-12-20');
        postLine((string) PRFFinancialAccountType::PAYBILL->value, 'income.tithe_and_offering', 1_500);
        postLine(
            (string) PRFFinancialAccountType::PAYBILL->value,
            'expense.desk.' . PRFResponsibleDesk::PRAYER_DESK->value,
            1_200,
        );

        $event = AccountingEvent::factory()->create();
        CreateRefundJob::dispatchSync([
            'accounting_event_ulid' => $event->ulid,
            'amount' => 300,
            'confirmation_message' => 'QA1 Confirmed',
        ]);

        $this->book = workbook(
            new CashbookExport(Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'), 'Parkroad Fellowship'),
        );
    });

    it('has a cashbook per account and the statements', function () {
        expect($this->book->getSheetNames())
            ->toContain(
                'Treasurer Report',
                'Paybill 2026',
                'M-Pesa 2026',
                'Bank 2026',
                'Cash 2026',
                'Cash Balances',
                'Income Statement',
                'Income Distribution',
            );
    });

    it('carries the opening balance and a running balance formula', function () {
        $sheet = $this->book->getSheetByName('Paybill 2026');

        expect($sheet->getCell('A1')->getValue())
            ->toBe('Parkroad Fellowship 2026 Paybill Account')
            ->and($sheet->getCell('G3')->getValue())
            ->toBe(5_000)
            ->and($sheet->getCell('G4')->getValue())
            ->toBe('=G3+E4-F4')
            ->and($sheet->getCell('G7')->getCalculatedValue())
            ->toEqual(5_000 + 1_500 - 1_200 + 300);
    });

    it('keeps refunds out of income and nets them against the desk', function () {
        $sheet = $this->book->getSheetByName('Income Statement');
        $values = collect($sheet->toArray(null, true, false))
            ->mapWithKeys(fn(array $row) => [(string) $row[0] => $row[1]]);

        expect($values->get('Tithe & Offering'))
            ->toEqual(1_500)
            ->and($values->get('Member Contribution'))
            ->toEqual(0)
            ->and($values->get('Prayer Desk'))
            ->toEqual(1_200)
            ->and($values->get('Mission Desk'))
            ->toEqual(-300);
    });
});

it('builds the monthly accountability workbook like the missions sheet', function () {
    $event = AccountingEvent::factory()->create(['name' => 'Isinya Boys Sunday Service', 'due_date' => '2026-03-08']);
    AllocationEntry::factory()->for($event)->credit(2_650)->create();
    AllocationEntry::factory()->for($event)->debit(1_700)->create();
    AllocationEntry::factory()->for($event)->debit(402)->create();
    Refund::factory()->for($event)->create(['amount' => 548]);

    $sheet = workbook(new MonthlyAccountabilityExport(Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31')))
        ->getSheetByName('March 2026');
    $header = collect($sheet->rangeToArray('A1:Z1', null, false, false)[0])->filter()->values();

    expect($header->all())
        ->toContain(
            'S/NO',
            'Accounting event',
            'Amount disbursed (Ksh)',
            'Expenses',
            'Token of appreciation',
            'Refund Done',
            'Balance',
            'Remarks',
        )
        ->and($sheet->getMergeCells())
        ->toHaveKey('A1:A2')
        ->and($sheet->getCell('C3')->getValue())
        ->toBe('Isinya Boys Sunday Service');

    $balanceColumn = collect($sheet->rangeToArray('A1:Z1', null, false, false, true)[1])->search('Balance');

    expect($sheet->getCell("{$balanceColumn}3")->getCalculatedValue())->toEqual(0);
});

it('generates a requested report, notifies the requester and serves it for download', function () {
    Notification::fake();
    postLine((string) PRFFinancialAccountType::CASH->value, 'income.other', 700);

    $response = actingAsTenantUser()->postJson(route('api.financial-reports.store'), [
        'type' => PRFFinancialReportType::CASHBOOK->value,
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ])->assertCreated();

    $report = FinancialReport::query()->where('ulid', $response->json('data.ulid'))->sole();

    expect($report->status)
        ->toBe(PRFProcessingStatus::COMPLETED)
        ->and(FinancialReport::disk()->exists((string) $report->file_path))
        ->toBeTrue();
    Notification::assertSentTo($report->requestedBy, FinancialReportReadyNotification::class);

    $url = actingAsTenantUser()->getJson(route('api.financial-reports.show', $report->ulid))->json('data.download_url');

    $this->get($url)->assertOk()->assertDownload($report->downloadName());
});

it('renders the impact summary as a PDF', function () {
    fakePDFRendering();

    $report = CreateFinancialReportJob::dispatchSync([
        'type' => PRFFinancialReportType::IMPACT_SUMMARY->value,
        'period_start' => '2026-01-01',
        'period_end' => '2026-03-31',
    ]);

    expect($report->fresh())->status->toBe(PRFProcessingStatus::COMPLETED)->file_path->toEndWith('.pdf');
});

it('sends last month\'s reports to the treasurer and chair desks', function () {
    Notification::fake();
    fakePDFRendering();
    AppSetting::set('desk_emails.treasurers', ['treasurer@example.org'], 'desk_emails', 'array');

    $tenant = tenant();

    $this->artisan('prf:finance:send-monthly-reports', ['--month' => '2026-03-15'])->assertSuccessful();

    initTenancy($tenant);

    expect(FinancialReport::query()->pluck('type')->all())->toEqualCanonicalizing([
        PRFFinancialReportType::MONTHLY_ACCOUNTABILITY,
        PRFFinancialReportType::IMPACT_SUMMARY,
    ]);

    Notification::assertSentOnDemandTimes(FinancialReportReadyNotification::class, 2);
});

function readyCashbookReport(): FinancialReport
{
    postLine((string) PRFFinancialAccountType::CASH->value, 'income.other', 700);

    return CreateFinancialReportJob::dispatchSync([
        'type' => PRFFinancialReportType::CASHBOOK->value,
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ])->fresh();
}

it('puts the ready report in the panel inbox with a download link', function () {
    Filament::setCurrentPanel('admin');
    $report = readyCashbookReport();
    $notification = new FinancialReportReadyNotification($report);
    $user = User::factory()->make();

    $message = $notification->toDatabase($user);

    expect($notification->via($user))
        ->toBe(['database', 'mail'])
        ->and($notification->via(Notification::route('mail', 'desk@example.org')))
        ->toBe(['mail'])
        ->and(new FinancialReportReadyNotification($report, emailOnly: true)->via($user))
        ->toBe(['mail'])
        ->and($message['actions'][0]['url'])
        ->toBe(route('filament.admin.finance.reports.download', ['ulid' => $report->ulid], false));
});

it('downloads a ready report from the panel', function () {
    actingAsTenantUser();
    $report = readyCashbookReport();

    $response = app(DownloadFinancialReportController::class)($report->ulid);

    expect($response)
        ->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('content-disposition'))
        ->toContain('Cashbook');
});

it('sends the treasurer back to the reports list when the file is gone', function () {
    Filament::setCurrentPanel('admin');
    actingAsTenantUser();
    $report = readyCashbookReport();
    FinancialReport::disk()->delete((string) $report->file_path);

    $response = app(DownloadFinancialReportController::class)($report->ulid);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
});
