<?php

namespace App\Jobs\FinancialAccount;

use App\Models\FinancialAccount;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): FinancialAccount
    {
        $financialAccount = FinancialAccount::query()->where('ulid', $this->ulid)->firstOrFail();

        $financialAccount->update($this->data);

        return $financialAccount;
    }
}
