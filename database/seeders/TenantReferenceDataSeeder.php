<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Reference data every tenant needs to operate, seeded when a tenant is provisioned and by
 * prf:tenants:seed-reference-data for existing tenants. Every seeder here must be idempotent.
 */
class TenantReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        new SpiritualYearSeeder()->run();
        new TransferRateSeeder()->run();
        new ExpenseCategorySeeder()->run();
        new PaymentTypeSeeder()->run();
        new FinancialAccountSeeder()->run();
        new LedgerCategorySeeder()->run();
    }
}
