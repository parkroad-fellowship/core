<?php

namespace App\Console\Commands\Tenant;

use App\Console\Concerns\RunsForEachTenant;
use App\Models\Tenant;
use Database\Seeders\TenantReferenceDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Makes sure every tenant has the reference data a new tenant gets: the treasurer's chart of
 * accounts, expense categories, payment types, transfer rates and spiritual years.
 *
 * Runs on every container start (.fly/scripts/migrate.sh). It only adds what is missing: it never
 * overwrites the treasurer's edits or revives anything they deleted, so running it again is safe.
 * Run with --isolated so two machines booting together don't seed at the same time.
 */
class SeedReferenceDataCommand extends Command implements Isolatable
{
    use RunsForEachTenant;

    protected $signature = 'prf:tenants:seed-reference-data {--tenant= : Only this tenant id}';

    protected $description = 'Add any missing reference data (chart of accounts, expense categories, transfer rates…) to every tenant';

    public function handle(): int
    {
        $only = $this->option('tenant');
        $failed = 0;

        $this->forEachTenant(
            function (Tenant $tenant) use ($only, &$failed): void {
                if (is_string($only) && $only !== '' && $tenant->id !== $only) {
                    return;
                }

                try {
                    DB::transaction(fn() => new TenantReferenceDataSeeder()->run());

                    $this->info("Reference data up to date for {$tenant->name}.");
                } catch (Throwable $exception) {
                    // One tenant's problem must not stop the others (or the container) from starting.
                    $failed++;
                    $this->error("Could not seed {$tenant->name}: {$exception->getMessage()}");
                    Log::error('Reference data seeding failed', [
                        'tenant' => $tenant->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            },
            activeOnly: false,
        );

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
