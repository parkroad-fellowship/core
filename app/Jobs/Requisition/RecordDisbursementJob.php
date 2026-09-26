<?php

namespace App\Jobs\Requisition;

use App\Models\FinancialAccount;
use App\Models\Requisition;
use App\Services\Finance\Ledger;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class RecordDisbursementJob
{
    use Dispatchable;

    /**
     * @param  array{financial_account_ulid: string, charge?: int, reference?: string, paid_on?: string}  $data
     */
    public function __construct(
        public string $ulid,
        public array $data = [],
        public ?int $recordedBy = null,
    ) {}

    /**
     * Books an approved requisition's payout in the cashbook. The ledger call is idempotent,
     * so recording the same disbursement twice posts only once.
     */
    public function handle(Ledger $ledger): Requisition
    {
        throw_unless(
            filled($this->data['financial_account_ulid'] ?? null),
            InvalidArgumentException::class,
            'financial_account_ulid is required.',
        );

        $requisition = Requisition::query()->where('ulid', $this->ulid)->firstOrFail();
        $account = FinancialAccount::query()->where('ulid', $this->data['financial_account_ulid'])->firstOrFail();

        $ledger->postDisbursement(
            requisition: $requisition,
            account: $account,
            charge: (int) ($this->data['charge'] ?? 0),
            reference: $this->data['reference'] ?? null,
            paidOn: filled($this->data['paid_on'] ?? null) ? Carbon::parse($this->data['paid_on']) : null,
            recordedBy: $this->recordedBy,
        );

        return $requisition->refresh();
    }
}
