<?php

namespace App\Jobs\Department;

use App\Models\Department;
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

    public function handle(): Department
    {
        $department = Department::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $department->update($attributes);

        return $department;
    }
}
