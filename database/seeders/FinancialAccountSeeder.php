<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class FinancialAccountSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('prf.finance.accounts') as $account) {
            FinancialAccount::withTrashed()->firstOrCreate(['name' => $account['name']], $account);
        }
    }
}
