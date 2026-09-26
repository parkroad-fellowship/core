<?php

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerFlow;
use App\Enums\PRFPaymentStatus;
use App\Enums\PRFPledgeInstallmentMethod;
use App\Enums\PRFResponsibleDesk;
use App\Events\LedgerEntry\IncomeReceipted;
use App\Events\Payment\PaymentSucceeded;
use App\Jobs\AccountTransfer\CreateJob as CreateTransferJob;
use App\Jobs\AllocationEntry\AddTokenJob;
use App\Jobs\LedgerEntry\CreateJob as CreateLedgerEntryJob;
use App\Jobs\Pledge\RecordInstallmentJob;
use App\Jobs\Refund\CreateJob as CreateRefundJob;
use App\Jobs\Requisition\ApproveJob;
use App\Listeners\Payment\PostPaymentToLedger;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Pledge;
use App\Models\Requisition;
use App\Models\User;
use App\Services\Finance\ChartOfAccounts;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->chart = app(ChartOfAccounts::class);
    $this->paybill = $this->chart->account(PRFFinancialAccountType::PAYBILL);
    $this->bank = $this->chart->account(PRFFinancialAccountType::BANK);
});

function receiptIncome(FinancialAccount $account, int $amount, array $overrides = []): LedgerEntry
{
    return CreateLedgerEntryJob::dispatchSync([
        'financial_account_ulid' => $account->ulid,
        'ledger_category_ulid' => app(ChartOfAccounts::class)->category('income.member_contribution')->ulid,
        'amount' => $amount,
        'transacted_on' => '2026-03-02',
        'counterparty' => 'Jane Giver',
        ...$overrides,
    ]);
}

describe('balances and receipts', function () {
    it('computes each account balance from receipts and payments', function () {
        receiptIncome($this->paybill, 5_000);
        CreateLedgerEntryJob::dispatchSync([
            'financial_account_ulid' => $this->paybill->ulid,
            'ledger_category_ulid' => $this->chart->expenseFor(PRFResponsibleDesk::PRAYER_DESK)->ulid,
            'amount' => 1_200,
        ]);

        expect($this->paybill->fresh()->balance)
            ->toBe(3_800)
            ->and(FinancialAccount::query()->withBalance()->find($this->paybill->id)->balance)
            ->toBe(3_800);
    });

    it('numbers income receipts per year and never numbers other lines', function () {
        $first = receiptIncome($this->paybill, 100);
        $second = receiptIncome($this->paybill, 200);
        $expense = CreateLedgerEntryJob::dispatchSync([
            'financial_account_ulid' => $this->paybill->ulid,
            'ledger_category_ulid' => $this->chart->expenseFor(PRFResponsibleDesk::MISSIONS_DESK)->ulid,
            'amount' => 50,
        ]);

        expect($first->receipt_number)
            ->toBe('PRF-2026-000001')
            ->and($second->receipt_number)
            ->toBe('PRF-2026-000002')
            ->and($expense->receipt_number)
            ->toBeNull()
            ->and($expense->flow)
            ->toBe(PRFLedgerFlow::PAYMENT);
    });

    it('announces receipted income so a receipt can be sent', function () {
        Event::fake([IncomeReceipted::class]);

        receiptIncome($this->paybill, 1_000, ['send_receipt' => true, 'giver_email' => 'jane@example.com']);

        Event::assertDispatched(IncomeReceipted::class, fn(IncomeReceipted $event) => $event->sendReceipt);
    });

    it('settles a membership fee when income is receipted against it', function () {
        $membership = Membership::factory()->create(['approved' => false, 'amount' => 0]);

        receiptIncome($this->paybill, 500, [
            'ledger_category_ulid' => $this->chart->category('income.member_subscription')->ulid,
            'membership_ulid' => $membership->ulid,
        ]);

        expect($membership->fresh())->approved->toBeTrue()->amount->toBe(500);
    });
});

describe('transfers', function () {
    it('moves money between accounts without touching income or expense', function () {
        receiptIncome($this->paybill, 10_000);

        $transfer = CreateTransferJob::dispatchSync([
            'from_financial_account_ulid' => $this->paybill->ulid,
            'to_financial_account_ulid' => $this->bank->ulid,
            'amount' => 8_000,
            'charge' => 83,
            'transferred_on' => '2026-03-05',
        ]);

        expect($this->paybill->fresh()->balance)
            ->toBe(10_000 - 8_000 - 83)
            ->and($this->bank->fresh()->balance)
            ->toBe(8_000)
            ->and(LedgerEntry::query()->ofKind(PRFLedgerCategoryKind::INCOME)->sum('amount'))
            ->toEqual(10_000)
            ->and($transfer->ledgerEntries()->count())
            ->toBe(3);
    });

    it('removes a deleted transfer from the cashbook', function () {
        $transfer = CreateTransferJob::dispatchSync([
            'from_financial_account_ulid' => $this->paybill->ulid,
            'to_financial_account_ulid' => $this->bank->ulid,
            'amount' => 1_000,
            'transferred_on' => '2026-03-05',
        ]);

        $transfer->delete();

        expect($this->bank->fresh()->balance)->toBe(0)->and($this->paybill->fresh()->balance)->toBe(0);
    });
});

describe('auto-posting', function () {
    it('books a Paystack gift gross with the fee as a charge, once', function () {
        Event::fake([IncomeReceipted::class]);

        $payment = Payment::factory()->create([
            'amount' => 1_000,
            'payment_status' => PRFPaymentStatus::SUCCESS,
            'reference' => 'PSK-123',
            'transaction_meta' => ['fees' => 2_500, 'customer' => ['email' => 'giver@example.com']],
        ]);

        $listener = app(PostPaymentToLedger::class);
        $listener->handle(new PaymentSucceeded($payment));
        $listener->handle(new PaymentSucceeded($payment));

        $paystack = $this->chart->account(PRFFinancialAccountType::PAYSTACK);

        expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())
            ->toBe(2)
            ->and($paystack->fresh()->balance)
            ->toBe(975)
            ->and(LedgerEntry::query()->where('source_key', "payment:{$payment->id}:gift")->sole())
            ->amount->toBe(1_000)
            ->giver_email->toBe('giver@example.com');

        Event::assertDispatchedTimes(IncomeReceipted::class, 1);
    });

    it('books the disbursement when a requisition is approved from an account', function () {
        $approver = Member::factory()->create();
        $requisition = Requisition::factory()->create([
            'responsible_desk' => PRFResponsibleDesk::PRAYER_DESK,
            'total_amount' => 6_000,
        ]);

        ApproveJob::dispatchSync(
            $requisition->ulid,
            [
                'financial_account_ulid' => $this->paybill->ulid,
                'charge' => 78,
            ],
            $approver->user_id,
        );

        $lines = LedgerEntry::query()->where('requisition_id', $requisition->id)->get();

        expect($requisition->fresh()->approval_status)
            ->toBe(PRFApprovalStatus::APPROVED)
            ->and($lines->sum('amount'))
            ->toBe(6_078)
            ->and($lines->firstWhere('amount', 6_000)?->ledgerCategory->responsible_desk)
            ->toBe(PRFResponsibleDesk::PRAYER_DESK)
            ->and($this->paybill->fresh()->balance)
            ->toBe(-6_078);
    });

    it('treats tokens inside a refund as income and only the rest as a refund', function () {
        $event = AccountingEvent::factory()->create();
        AllocationEntry::factory()->for($event)->token(30_000)->create();

        CreateRefundJob::dispatchSync([
            'accounting_event_ulid' => $event->ulid,
            'amount' => 34_418,
            'confirmation_message' => 'QWE123 Confirmed',
        ]);

        $lines = LedgerEntry::query()->where('accounting_event_id', $event->id)->with('ledgerCategory')->get();

        expect($lines->firstWhere('ledgerCategory.kind', PRFLedgerCategoryKind::INCOME)?->amount)
            ->toBe(30_000)
            ->and($lines->firstWhere('ledgerCategory.kind', PRFLedgerCategoryKind::REFUND)?->amount)
            ->toBe(4_418)
            ->and($lines->every(fn(LedgerEntry $line) => $line->financial_account_id === $this->paybill->id))
            ->toBeTrue();
    });

    it('books a token handed straight to the treasurer and does not count it again in the refund', function () {
        $event = AccountingEvent::factory()->create();
        $member = Member::factory()->create();

        AddTokenJob::dispatchSync([
            'accounting_event_ulid' => $event->ulid,
            'member_ulid' => $member->ulid,
            'entry_type' => PRFEntryType::CREDIT->value,
            'unit_cost' => 2_000,
            'confirmation_message' => 'Cash',
            'narration' => 'Ruthimitu girls appreciation',
            'financial_account_ulid' => $this->chart->account(PRFFinancialAccountType::CASH)->ulid,
        ]);

        CreateRefundJob::dispatchSync([
            'accounting_event_ulid' => $event->ulid,
            'amount' => 500,
            'confirmation_message' => 'QWE124 Confirmed',
        ]);

        expect(
            LedgerEntry::query()
                ->where('accounting_event_id', $event->id)
                ->ofKind(PRFLedgerCategoryKind::INCOME)
                ->sum('amount'),
        )
            ->toEqual(2_000)
            ->and(
                LedgerEntry::query()
                    ->where('accounting_event_id', $event->id)
                    ->ofKind(PRFLedgerCategoryKind::REFUND)
                    ->sum('amount'),
            )
            ->toEqual(500);
    });

    it('receipts an offline pledge installment into its account', function () {
        Event::fake([IncomeReceipted::class]);
        $pledge = Pledge::factory()->create(['email' => 'pledger@example.com']);

        $installment = RecordInstallmentJob::dispatchSync([
            'pledge_ulid' => $pledge->ulid,
            'amount' => 2_500,
            'method' => PRFPledgeInstallmentMethod::MPESA->value,
            'financial_account_ulid' => $this->chart->account(PRFFinancialAccountType::MPESA)->ulid,
        ], User::factory()->create());

        expect($installment->method)
            ->toBe(PRFPledgeInstallmentMethod::MPESA)
            ->and($installment->ledgerEntry)
            ->amount->toBe(2_500)
            ->giver_email->toBe('pledger@example.com')
            ->receipt_number->not->toBeNull();

        Event::assertDispatched(IncomeReceipted::class);
    });
});
