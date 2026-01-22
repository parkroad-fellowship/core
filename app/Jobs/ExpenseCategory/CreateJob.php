<?php

namespace App\Jobs\ExpenseCategory;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): ExpenseCategory
    {
        return ExpenseCategory::create($this->data);
    }
}
