<?php

namespace App\Console\Commands\Finance;

use App\Console\Concerns\RunsForEachTenant;
use App\Enums\PRFProcessingStatus;
use App\Models\LedgerImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes workbook uploads older than 24h and fails pending imports left behind by them,
 * so abandoned uploads never sit on disk or block the treasurer's list.
 */
class PruneImportsCommand extends Command
{
    use RunsForEachTenant;

    protected $signature = 'prf:finance:prune-imports';

    protected $description = 'Delete finance-imports uploads older than 24h and mark stale pending imports as failed';

    public function handle(): int
    {
        $this->forEachTenant(function (): void {
            $disk = Storage::disk(LedgerImport::DISK);
            $cutoff = now()->subDay()->timestamp;

            foreach ($disk->allFiles('finance-imports') as $file) {
                if ($disk->lastModified($file) < $cutoff) {
                    $disk->delete($file);
                }
            }

            LedgerImport::query()
                ->where('status', PRFProcessingStatus::PENDING)
                ->where('created_at', '<', now()->subDay())
                ->get()
                ->each(fn(LedgerImport $import) => $import->update([
                    'status' => PRFProcessingStatus::FAILED,
                    'error' => 'Pruned: the upload expired before the import started.',
                    'completed_at' => now(),
                ]));
        });

        $this->info('Pruned stale workbook imports.');

        return self::SUCCESS;
    }
}
