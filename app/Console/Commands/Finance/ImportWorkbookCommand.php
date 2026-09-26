<?php

namespace App\Console\Commands\Finance;

use App\Enums\PRFProcessingStatus;
use App\Jobs\LedgerImport\ImportWorkbookJob;
use App\Models\LedgerImport;
use App\Models\Tenant;
use App\Services\Finance\WorkbookImporter;
use Illuminate\Console\Command;

/**
 * Secondary path for the workbook import (the web upload is primary): prints the preview
 * and, without --dry-run, posts the workbook through the same queued job run synchronously.
 */
class ImportWorkbookCommand extends Command
{
    protected $signature = 'prf:finance:import-workbook {path : Workbook file} {--tenant= : Tenant id} {--year= : Cashbook year (defaults to current year)} {--dry-run : Print the preview without importing}';

    protected $description = "Preview the treasurer's cashbook workbook, and import it unless --dry-run is given";

    public function handle(WorkbookImporter $importer): int
    {
        $tenantOption = $this->option('tenant');
        $initialized = false;

        if (is_string($tenantOption) && $tenantOption !== '') {
            tenancy()->initialize(Tenant::query()->findOrFail($tenantOption));
            $initialized = true;
        }

        try {
            if (!tenancy()->initialized) {
                $this->error('No tenant context. Pass --tenant=<id> (the web upload is the primary path).');

                return self::FAILURE;
            }

            $year = $this->option('year') !== null ? (int) $this->option('year') : now()->year;
            $preview = $importer->preview((string) $this->argument('path'), $year);

            $this->table(
                ['Sheet', 'Account', 'Rows', 'Mapped', 'Unmapped', 'Receipts (KES)', 'Payments (KES)'],
                array_map(fn(array $sheet): array => [
                    $sheet['sheet'],
                    $sheet['account_name'] ?? $sheet['account_type'] . ' (to create)',
                    $sheet['rows'],
                    $sheet['mapped'],
                    $sheet['unmapped'],
                    number_format($sheet['receipts']),
                    number_format($sheet['payments']),
                ], $preview->sheets),
            );

            if ($preview->unmapped !== []) {
                $this->warn('Unmapped labels:');
                $this->table(['Label', 'Rows', 'Sheets', 'Example'], array_map(fn(array $entry): array => [
                    $entry['label'],
                    $entry['count'],
                    implode(', ', $entry['sheets']),
                    $entry['example'],
                ], array_values($preview->unmapped)));
            }

            $this->info(
                'Paired transfers: '
                . count($preview->pairedTransfers)
                . '. Unpaired: '
                . count($preview->unpairedTransfers)
                . '.',
            );

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            $import = LedgerImport::create([
                'file_path' => (string) $this->argument('path'),
                'original_name' => basename((string) $this->argument('path')),
                'year' => $year,
                'status' => PRFProcessingStatus::PENDING,
            ]);

            ImportWorkbookJob::dispatchSync($import);

            $this->info("Imported: {$import->fresh()?->status->getLabel()}.");

            return self::SUCCESS;
        } finally {
            if ($initialized) {
                tenancy()->end();
            }
        }
    }
}
