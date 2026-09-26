<?php

namespace Database\Seeders;

use App\Models\LedgerCategory;
use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class LedgerCategorySeeder extends Seeder
{
    /**
     * Creates the chart of accounts. Existing categories keep the treasurer's renames.
     */
    public function run(): void
    {
        foreach (config('prf.finance.categories') as $sort => $category) {
            LedgerCategory::withTrashed()->firstOrCreate(['code' => $category['code']], [
                ...$category,
                'sort' => ($sort + 1) * 10,
            ]);
        }

        foreach (config('prf.finance.payment_type_categories') as $paymentTypeName => $code) {
            $paymentType = PaymentType::query()
                ->where('name', $paymentTypeName)
                ->whereNull('ledger_category_id')
                ->first();

            $paymentType?->update(['ledger_category_id' => LedgerCategory::query()->where('code', $code)->value('id')]);
        }
    }
}
