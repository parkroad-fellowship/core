<?php

namespace App\Jobs\PrayerResponse;

use App\Models\Member;
use App\Models\PrayerPrompt;
use App\Models\PrayerResponse;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): PrayerResponse
    {
        $data = $this->data;

        // Create a new prayer response
        return PrayerResponse::create([
            'prayer_prompt_id' => PrayerPrompt::query()->where('ulid', $data['prayer_prompt_ulid'])->first()->id,
            'member_id' => Member::query()->where('ulid', $data['member_ulid'])->first()->id,
        ]);
    }
}
