<?php

namespace App\Jobs\Member;

use App\Models\Church;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Profession;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Member
    {
        $data = $this->data;

        if (isset($data['church_ulid'])) {
            $data['church_id'] = Church::where('ulid', $data['church_ulid'])->firstOrFail()->id;
        }
        Arr::forget($data, ['church_ulid']);

        if (isset($data['profession_ulid'])) {
            $data['profession_id'] = Profession::where('ulid', $data['profession_ulid'])->firstOrFail()->id;
        }
        Arr::forget($data, ['profession_ulid']);

        if (isset($data['marital_status_ulid'])) {
            $data['marital_status_id'] = MaritalStatus::where('ulid', $data['marital_status_ulid'])->firstOrFail()->id;
        }
        Arr::forget($data, ['marital_status_ulid']);

        return Member::create($data);
    }
}
