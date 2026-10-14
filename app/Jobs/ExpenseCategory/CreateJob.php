<?php

namespace App\Jobs\ExpenseCategory;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): ExpenseCategory
    {
        return ExpenseCategory::create($this->data);
    }
}
