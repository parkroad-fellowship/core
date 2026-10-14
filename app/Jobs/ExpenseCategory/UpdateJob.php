<?php

namespace App\Jobs\ExpenseCategory;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): ExpenseCategory
    {
        $expenseCategory = ExpenseCategory::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $expenseCategory->update($attributes);

        return $expenseCategory;
    }
}
