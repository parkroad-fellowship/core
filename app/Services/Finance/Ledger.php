<?php

namespace App\Services\Finance;

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Models\AllocationEntry;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Requisition;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Posts lines to the treasurer's cashbook. Every write to `ledger_entries` goes through here, so
 * receipt numbering, default flows/channels and idempotency are applied the same way everywhere.
 */
class Ledger
{
    private const RECEIPT_NUMBER_ATTEMPTS = 5;

    public function __construct(
        private readonly ChartOfAccounts $chart,
    ) {}

    /**
     * Post one line. A line with a `source_key` is posted once; replays return the existing line.
     *
     * @param  array<string, mixed>  $attributes  LedgerEntry attributes (ids, not ULIDs)
     */
    public function post(array $attributes): LedgerEntry
    {
        $sourceKey = $attributes['source_key'] ?? null;

        if (is_string($sourceKey) && $sourceKey !== '') {
            $existing = LedgerEntry::withTrashed()->where('source_key', $sourceKey)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $category = LedgerCategory::query()->findOrFail($attributes['ledger_category_id']);
        $account = FinancialAccount::query()->findOrFail($attributes['financial_account_id']);

        $attributes['flow'] ??= $category->kind->defaultFlow() ?? throw new InvalidArgumentException(
            'Transfer lines need an explicit flow.',
        );
        $attributes['channel'] ??= PRFLedgerChannel::defaultFor($account->type);
        $attributes['transacted_on'] ??= Carbon::today();

        $flow = $attributes['flow'] instanceof PRFLedgerFlow
            ? $attributes['flow']
            : PRFLedgerFlow::from((int) $attributes['flow']);

        if ($flow !== PRFLedgerFlow::RECEIPT || $category->kind !== PRFLedgerCategoryKind::INCOME) {
            return LedgerEntry::create(Arr::except($attributes, ['receipt_number']));
        }

        return $this->createWithReceiptNumber($attributes);
    }

    /**
     * Book a successful online gift: the gross amount as income, Paystack's fee as a charge.
     * The giver's receipt shows what they gave; the net equals what Paystack settles.
     *
     * @return array{gift: LedgerEntry, fee: LedgerEntry|null}
     */
    public function postPayment(Payment $payment): array
    {
        return DB::transaction(function () use ($payment): array {
            $account = $this->chart->account(PRFFinancialAccountType::PAYSTACK);
            $meta = (array) ($payment->transaction_meta ?? []);
            $giverEmail =
                Arr::get($meta, 'customer.email') ?? Arr::get($meta, 'email') ?? $payment->member?->personal_email;
            $category = $payment->paymentType?->ledger_category_id ?? $this->chart->category('income.other')->id;
            $transactedOn = Carbon::parse(Arr::get($meta, 'paid_at') ?? $payment->updated_at ?? now());

            $gift = $this->post([
                'financial_account_id' => $account->id,
                'ledger_category_id' => $category,
                'flow' => PRFLedgerFlow::RECEIPT,
                'channel' => PRFLedgerChannel::PAYSTACK,
                'amount' => (int) $payment->amount,
                'transacted_on' => $transactedOn,
                'counterparty' => $payment->member?->full_name ?? $giverEmail,
                'description' => 'Online giving: ' . ($payment->paymentType?->name ?? 'General'),
                'reference' => $payment->reference,
                'member_id' => $payment->member_id,
                'giver_email' => $giverEmail,
                'payment_id' => $payment->id,
                'source_key' => "payment:{$payment->id}:gift",
            ]);

            // Paystack reports fees in the currency's subunit (cents).
            $fee = (int) round((int) Arr::get($meta, 'fees', 0) / 100);

            $feeEntry = $fee > 0
                ? $this->post([
                    'financial_account_id' => $account->id,
                    'ledger_category_id' => $this->chart->transactionCosts()->id,
                    'flow' => PRFLedgerFlow::PAYMENT,
                    'channel' => PRFLedgerChannel::PAYSTACK,
                    'amount' => $fee,
                    'transacted_on' => $transactedOn,
                    'counterparty' => 'Paystack',
                    'description' => "Paystack fee on {$payment->reference}",
                    'reference' => $payment->reference,
                    'payment_id' => $payment->id,
                    'source_key' => "payment:{$payment->id}:fee",
                ])
                : null;

            return ['gift' => $gift, 'fee' => $feeEntry];
        });
    }

    /**
     * Money sent out for an approved requisition, plus the transfer charge, booked against the
     * requisition desk's expense line and linked to its accounting event.
     *
     * @return array{disbursement: LedgerEntry, charge: LedgerEntry|null}
     */
    public function postDisbursement(
        Requisition $requisition,
        FinancialAccount $account,
        int $charge = 0,
        ?string $reference = null,
        ?Carbon $paidOn = null,
        ?int $recordedBy = null,
    ): array {
        return DB::transaction(function () use (
            $requisition,
            $account,
            $charge,
            $reference,
            $paidOn,
            $recordedBy,
        ): array {
            $requisition->loadMissing(['member', 'paymentInstruction', 'accountingEvent']);
            $shared = [
                'financial_account_id' => $account->id,
                'flow' => PRFLedgerFlow::PAYMENT,
                'transacted_on' => $paidOn ?? Carbon::today(),
                'counterparty' => $requisition->paymentInstruction?->recipient_name ?? $requisition->member?->full_name,
                'reference' => $reference,
                'accounting_event_id' => $requisition->accounting_event_id,
                'requisition_id' => $requisition->id,
                'recorded_by' => $recordedBy,
            ];

            $disbursement = $this->post([
                ...$shared,
                'ledger_category_id' => $this->chart->expenseFor($requisition->responsible_desk)->id,
                'amount' => (int) $requisition->total_amount,
                'description' => 'Requisition: ' . ($requisition->accountingEvent?->name ?? $requisition->ulid),
                'source_key' => "requisition:{$requisition->id}:disbursement",
            ]);

            $chargeEntry = $charge > 0
                ? $this->post([
                    ...$shared,
                    'ledger_category_id' => $this->chart->transactionCosts()->id,
                    'amount' => $charge,
                    'description' => 'Charges on requisition disbursement',
                    'source_key' => "requisition:{$requisition->id}:charge",
                ])
                : null;

            return ['disbursement' => $disbursement, 'charge' => $chargeEntry];
        });
    }

    /**
     * A token of appreciation handed straight to the treasurer: income, linked to its event.
     */
    public function postToken(AllocationEntry $token, FinancialAccount $account, ?int $recordedBy = null): LedgerEntry
    {
        $token->loadMissing('accountingEvent');

        return $this->post([
            'financial_account_id' => $account->id,
            'ledger_category_id' => $this->chart->category('income.appreciation_from_schools')->id,
            'flow' => PRFLedgerFlow::RECEIPT,
            'amount' => (int) $token->amount,
            'transacted_on' => Carbon::parse($token->created_at ?? now()),
            'counterparty' => $token->accountingEvent?->name,
            'description' => $token->narration ?: 'Token of appreciation',
            'accounting_event_id' => $token->accounting_event_id,
            'allocation_entry_id' => $token->id,
            'source_key' => "allocation_entry:{$token->id}:token",
            'recorded_by' => $recordedBy,
        ]);
    }

    /**
     * Money returned after an event. It first covers tokens of appreciation the missioner collected
     * but hasn't handed over (booked as income); only the rest is a refund that reduces the desk's
     * expense. Refunds are never income.
     *
     * @return list<LedgerEntry>
     */
    public function postRefund(Refund $refund, FinancialAccount $account, ?int $recordedBy = null): array
    {
        return DB::transaction(function () use ($refund, $account, $recordedBy): array {
            $event = $refund->accountingEvent()->firstOrFail();
            $appreciation = $this->chart->category('income.appreciation_from_schools');

            $tokens = (int) $event->allocationEntries()->where('is_token_of_appreciation', true)->sum('amount');
            $tokensReceived = (int) LedgerEntry::query()
                ->where('accounting_event_id', $event->id)
                ->where('ledger_category_id', $appreciation->id)
                ->sum('amount');
            $tokenPortion = min((int) $refund->amount, max(0, $tokens - $tokensReceived));
            $refundPortion = (int) $refund->amount - $tokenPortion;

            $shared = [
                'financial_account_id' => $account->id,
                'flow' => PRFLedgerFlow::RECEIPT,
                'transacted_on' => Carbon::parse($refund->created_at ?? now()),
                'counterparty' => $event->name,
                'reference' => $refund->confirmation_message
                    ? str($refund->confirmation_message)->limit(250)->toString()
                    : null,
                'accounting_event_id' => $event->id,
                'refund_id' => $refund->id,
                'recorded_by' => $recordedBy,
            ];

            $entries = [];

            if ($tokenPortion > 0) {
                $entries[] = $this->post([
                    ...$shared,
                    'ledger_category_id' => $appreciation->id,
                    'amount' => $tokenPortion,
                    'description' => 'Token of appreciation (handed over with refund)',
                    'source_key' => "refund:{$refund->id}:token",
                ]);
            }

            if ($refundPortion > 0) {
                $entries[] = $this->post([
                    ...$shared,
                    'ledger_category_id' => $this->chart->refundFor($event->responsible_desk)->id,
                    'amount' => $refundPortion,
                    'description' => 'Refund of unspent funds',
                    'source_key' => "refund:{$refund->id}:refund",
                ]);
            }

            return $entries;
        });
    }

    /**
     * The next receipt number for the year of $date, e.g. PRF-2026-000042.
     */
    public function nextReceiptNumber(Carbon $date): string
    {
        $prefix = sprintf('%s-%s-', config('prf.finance.receipt_number_prefix', 'PRF'), $date->format('Y'));

        $last = LedgerEntry::withTrashed()->where('receipt_number', 'like', $prefix . '%')->max('receipt_number');
        $next = $last === null ? 1 : (int) substr((string) $last, strlen($prefix)) + 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Two treasurers receipting at once can race for the same number; the unique index decides and
     * the loser takes the next one. Each attempt runs in a savepoint so the outer transaction survives.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createWithReceiptNumber(array $attributes): LedgerEntry
    {
        $date = Carbon::parse($attributes['transacted_on']);

        for ($attempt = 1;; $attempt++) {
            try {
                return DB::transaction(fn(): LedgerEntry => LedgerEntry::create([
                    ...$attributes,
                    'receipt_number' => $this->nextReceiptNumber($date),
                ]));
            } catch (UniqueConstraintViolationException $exception) {
                if (
                    $attempt >= self::RECEIPT_NUMBER_ATTEMPTS
                    || !str_contains($exception->getMessage(), 'receipt_number')
                ) {
                    throw $exception;
                }
            }
        }
    }
}
