<?php

namespace App\Jobs\LedgerCategory;

use App\Models\LedgerCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): LedgerCategory
    {
        return LedgerCategory::create($this->data);
    }
}
