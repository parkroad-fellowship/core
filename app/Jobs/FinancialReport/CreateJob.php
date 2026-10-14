<?php

namespace App\Jobs\FinancialReport;

use App\Enums\PRFProcessingStatus;
use App\Models\FinancialReport;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Asks for a report; the workbook is generated on the queue and the requester is emailed.
 */
class CreateJob
{
    use Dispatchable;

    /**
     * @param  array{type: int, period_start: string, period_end: string, requested_by?: int|null}  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): FinancialReport
    {
        $report = FinancialReport::create([...$this->data, 'status' => PRFProcessingStatus::PENDING]);

        GenerateJob::dispatch($report);

        return $report;
    }
}
