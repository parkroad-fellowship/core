<?php

namespace App\Jobs\PrayerRequest;

use App\Models\Member;
use App\Models\PrayerRequest;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): PrayerRequest
    {
        $data = $this->data;

        return PrayerRequest::create([
            'member_id' => Member::query()->where('ulid', $data['member_ulid'])->value('id'),
            'title' => Arr::get($data, 'title'),
            'description' => $data['description'],
        ]);
    }
}
