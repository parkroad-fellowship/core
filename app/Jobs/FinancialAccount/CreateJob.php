<?php

namespace App\Jobs\FinancialAccount;

use App\Models\FinancialAccount;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): FinancialAccount
    {
        return FinancialAccount::create($this->data);
    }
}
