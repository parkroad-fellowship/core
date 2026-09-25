<?php

namespace App\Jobs\LedgerEntry;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\AccountingEvent;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): LedgerEntry
    {
        $entry = LedgerEntry::query()->where('ulid', $this->ulid)->firstOrFail();

        $entry->update($this->resolveULIDs($this->data, [
            'financial_account_ulid' => FinancialAccount::class,
            'ledger_category_ulid' => LedgerCategory::class,
            'member_ulid' => Member::class,
            'accounting_event_ulid' => AccountingEvent::class,
        ]));

        return $entry;
    }
}
