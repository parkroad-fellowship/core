<?php

namespace App\Jobs\FinancialReport;

use App\Enums\PRFFinancialReportType;
use App\Enums\PRFProcessingStatus;
use App\Enums\PRFResponsibleDesk;
use App\Exports\Finance\CashbookExport;
use App\Exports\Finance\IncomeDistributionExport;
use App\Exports\Finance\MonthlyAccountabilityExport;
use App\Helpers\Utils;
use App\Models\FinancialReport;
use App\Models\Member;
use App\Notifications\FinancialReport\FinancialReportReadyNotification;
use App\Services\Finance\ImpactSummaryService;
use App\Settings\TenantSettings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

use function Spatie\LaravelPdf\Support\pdf;

#[Queue('long')]
#[Tries(2)]
#[Timeout(300)]
class GenerateJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(
        public FinancialReport $report,
    ) {}

    public function uniqueId(): string
    {
        return $this->report->ulid;
    }

    public function handle(ImpactSummaryService $impact): void
    {
        $report = $this->report;
        $report->update(['status' => PRFProcessingStatus::PROCESSING, 'error' => null]);

        $from = $report->period_start->copy()->startOfDay();
        $to = $report->period_end->copy()->endOfDay();
        $organisation = TenantSettings::fromCurrentTenant()->organizationName;

        $contents = match ($report->type) {
            PRFFinancialReportType::CASHBOOK => Excel::raw(
                new CashbookExport($from, $to, $organisation),
                ExcelFormat::XLSX,
            ),
            PRFFinancialReportType::MONTHLY_ACCOUNTABILITY => Excel::raw(
                new MonthlyAccountabilityExport($from, $to),
                ExcelFormat::XLSX,
            ),
            PRFFinancialReportType::INCOME_DISTRIBUTION => Excel::raw(
                new IncomeDistributionExport($from, $to, $organisation),
                ExcelFormat::XLSX,
            ),
            PRFFinancialReportType::IMPACT_SUMMARY => pdf()
                ->view('prf.finance.impact-summary-pdf', [
                    'impact' => $impact->for($from, $to),
                    'organisation' => $organisation,
                ])
                ->generatePdfContent(),
        };

        $path = $report->storagePath();
        FinancialReport::disk()->put($path, $contents);

        $report->update(['status' => PRFProcessingStatus::COMPLETED, 'file_path' => $path, 'completed_at' => now()]);

        // Scheduled reports have no requester: they go to the treasurer and chair desks, as panel
        // users where the desk member has an account (so they see it in-app), otherwise by email.
        $recipients = $report->requestedBy !== null
            ? collect([$report->requestedBy])
            : Utils::deskRecipients(PRFResponsibleDesk::TREASURER_DESK)
                ->merge(Utils::deskRecipients(PRFResponsibleDesk::CHAIRPERSON))
                ->map(fn(object $recipient): object => $recipient instanceof Member && $recipient->user !== null
                    ? $recipient->user
                    : $recipient)
                ->unique(fn(object $recipient): string => $recipient instanceof Model
                    ? $recipient::class . ':' . $recipient->getKey()
                    : spl_object_hash($recipient));

        Notification::send($recipients, new FinancialReportReadyNotification($report));
    }

    public function failed(Throwable $exception): void
    {
        $this->report->update(['status' => PRFProcessingStatus::FAILED, 'error' => $exception->getMessage()]);

        Log::error('Financial report failed', ['report' => $this->report->ulid, 'error' => $exception->getMessage()]);
    }
}
