<?php

namespace App\Jobs\LedgerEntry;

use App\Events\LedgerEntry\IncomeReceipted;
use App\Jobs\Concerns\ResolvesULIDs;
use App\Jobs\Pledge\RecordInstallmentJob;
use App\Models\AccountingEvent;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Records a cashbook line keyed in by the treasurer: offline income (with a receipt), an expense
 * or a charge. Income for a pledge is recorded as a pledge installment; income for a membership
 * settles that membership's fee.
 */
class CreateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data  validated LedgerEntry\CreateRequest data, plus `recorded_by`
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(Ledger $ledger): LedgerEntry
    {
        $pledgeULID = Arr::pull($this->data, 'pledge_ulid');
        $sendReceipt = (bool) Arr::pull($this->data, 'send_receipt', false);

        if (is_string($pledgeULID) && $pledgeULID !== '') {
            return $this->recordPledgeInstallment($pledgeULID, $sendReceipt);
        }

        $entry = DB::transaction(function () use ($ledger): LedgerEntry {
            $attributes = $this->resolveULIDs($this->data, [
                'financial_account_ulid' => FinancialAccount::class,
                'ledger_category_ulid' => LedgerCategory::class,
                'member_ulid' => Member::class,
                'accounting_event_ulid' => AccountingEvent::class,
                'membership_ulid' => Membership::class,
            ]);

            $entry = $ledger->post($attributes);

            if ($entry->membership_id !== null) {
                $entry->membership?->update(['amount' => $entry->amount, 'approved' => true]);
            }

            return $entry;
        });

        if ($entry->receipt_number !== null) {
            IncomeReceipted::dispatch($entry, $sendReceipt);
        }

        return $entry;
    }

    private function recordPledgeInstallment(string $pledgeULID, bool $sendReceipt): LedgerEntry
    {
        $installment = RecordInstallmentJob::dispatchSync([
            ...Arr::only($this->data, [
                'amount',
                'financial_account_ulid',
                'ledger_category_ulid',
                'channel',
                'reference',
                'giver_email',
                'giver_phone',
            ]),
            'pledge_ulid' => $pledgeULID,
            'fulfilled_on' => $this->data['transacted_on'] ?? null,
            'notes' => $this->data['description'] ?? null,
            'send_receipt' => $sendReceipt,
        ], User::query()->find($this->data['recorded_by'] ?? null));

        return $installment->ledgerEntry()->firstOrFail();
    }
}
