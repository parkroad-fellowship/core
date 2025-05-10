<?php

namespace App\Jobs\PrayerRequest;

use App\Models\Member;
use App\Models\PrayerRequest;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

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
    public function handle(): PrayerRequest
    {
        $data = $this->data;

        return PrayerRequest::create([
            'member_id' => Member::query()
                ->where('ulid', $data['member_ulid'])
                ->value('id'),
            'title' => Arr::get($data, 'title'),
            'description' => $data['description'],
        ]);
    }
}
