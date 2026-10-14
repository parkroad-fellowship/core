<?php

namespace App\Jobs\LedgerCategory;

use App\Models\LedgerCategory;
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

    public function handle(): LedgerCategory
    {
        $ledgerCategory = LedgerCategory::query()->where('ulid', $this->ulid)->firstOrFail();

        $ledgerCategory->update($this->data);

        return $ledgerCategory;
    }
}
