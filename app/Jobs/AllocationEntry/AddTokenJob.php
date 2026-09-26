<?php

namespace App\Jobs\AllocationEntry;

use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\FinancialAccount;
use App\Models\Member;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AddTokenJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(Ledger $ledger): AllocationEntry
    {
        $data = $this->data;

        $accountingEvent = AccountingEvent::query()->where('ulid', $data['accounting_event_ulid'])->firstOrFail();
        $data['accounting_event_id'] = $accountingEvent->id;
        Arr::forget($data, 'accounting_event_ulid');

        $member = Member::query()->where('ulid', $data['member_ulid'])->firstOrFail();
        $data['member_id'] = $member->id;
        Arr::forget($data, 'member_ulid');

        $data['amount'] = intval($data['unit_cost']);
        $data['quantity'] = 1;
        $data['charge'] = 0;
        $data['is_token_of_appreciation'] = true;

        $accountULID = Arr::pull($data, 'financial_account_ulid');

        return DB::transaction(function () use ($data, $accountULID, $ledger): AllocationEntry {
            $token = AllocationEntry::create($data);

            // Handed straight to the treasurer; otherwise it arrives with the missioner's refund.
            if (is_string($accountULID) && $accountULID !== '') {
                $ledger->postToken($token, FinancialAccount::query()->where('ulid', $accountULID)->firstOrFail());
            }

            return $token;
        });
    }
}
