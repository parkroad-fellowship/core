<?php

namespace App\Jobs\LedgerImport;

use App\Enums\PRFProcessingStatus;
use App\Models\LedgerImport;
use App\Notifications\LedgerImport\LedgerImportCompletedNotification;
use App\Services\Finance\WorkbookImporter;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Posts a treasurer workbook upload to the cashbook. One attempt only: the importer's
 * source keys make a retry or re-upload safe, and the uploaded file is deleted afterwards.
 */
#[Queue('long')]
#[Tries(1)]
#[Timeout(580)]
class ImportWorkbookJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(
        public LedgerImport $ledgerImport,
    ) {}

    public function uniqueId(): string
    {
        return $this->ledgerImport->ulid;
    }

    public function handle(WorkbookImporter $importer): void
    {
        $import = LedgerImport::query()->where('ulid', $this->ledgerImport->ulid)->firstOrFail();

        // Only imports the treasurer confirmed (queued as PROCESSING) run; discarded ones don't.
        if ($import->status !== PRFProcessingStatus::PROCESSING || $import->completed_at !== null) {
            return;
        }

        try {
            $summary = $importer->import($import->fresh() ?? $import);

            $import->update([
                'status' => PRFProcessingStatus::COMPLETED,
                'summary' => $summary,
                'error' => null,
                'completed_at' => now(),
            ]);

            $this->notifyImporter($import->fresh() ?? $import);
        } catch (Throwable $exception) {
            Log::error('Workbook import failed', [
                'import' => $import->ulid,
                'error' => $exception->getMessage(),
            ]);

            $import->update([
                'status' => PRFProcessingStatus::FAILED,
                'error' => Str::limit($exception->getMessage(), 1000),
                'completed_at' => now(),
            ]);

            $this->notifyImporter($import->fresh() ?? $import);

            throw $exception;
        } finally {
            Storage::disk(LedgerImport::DISK)->delete((string) $import->file_path);
        }
    }

    private function notifyImporter(LedgerImport $import): void
    {
        $importer = $import->importedBy;

        if ($importer !== null) {
            $importer->notify(new LedgerImportCompletedNotification($import));
        }
    }
}
