<?php

namespace App\Jobs\Membership;

use App\Models\Member;
use App\Models\Membership;
use App\Models\SpiritualYear;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Membership
    {
        $member = Member::query()->where('ulid', $this->data['member_ulid'])->firstOrFail();
        $spiritualYear = SpiritualYear::query()->where('ulid', $this->data['spiritual_year_ulid'])->firstOrFail();

        return Membership::create([
            'member_id' => $member->id,
            'spiritual_year_id' => $spiritualYear->id,
            'type' => $this->data['type'],
            'approved' => $this->data['approved'] ?? false,
            'amount' => $this->data['amount'] ?? null,
        ]);
    }
}
