<?php

namespace App\Console\Commands\Tenant;

use App\Console\Concerns\RunsForEachTenant;
use App\Models\Tenant;
use Database\Seeders\TenantReferenceDataSeeder;
use Illuminate\Console\Command;

class SeedReferenceDataCommand extends Command
{
    use RunsForEachTenant;

    protected $signature = 'prf:tenants:seed-reference-data {--tenant= : Only this tenant id}';

    protected $description = 'Seed the reference data new tenants get (chart of accounts, expense categories, transfer rates…) into existing tenants';

    public function handle(): int
    {
        $only = $this->option('tenant');

        $this->forEachTenant(function (Tenant $tenant) use ($only): void {
            if (is_string($only) && $only !== '' && $tenant->id !== $only) {
                return;
            }

            new TenantReferenceDataSeeder()->run();

            $this->info("Seeded reference data for {$tenant->name}.");
        }, activeOnly: false);

        return self::SUCCESS;
    }
}
