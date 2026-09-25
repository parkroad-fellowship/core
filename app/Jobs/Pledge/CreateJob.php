<?php

namespace App\Jobs\Pledge;

use App\Enums\PRFPledgeStatus;
use App\Models\Member;
use App\Models\Pledge;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Pledge
    {
        $data = $this->data;
        $email = Arr::get($data, 'email');

        // Optionally link to an existing member so their app giving history and
        // pledge reconcile. Unregistered givers stay tied to the pledge alone.
        $memberId = null;

        if (filled(Arr::get($data, 'member_ulid'))) {
            $memberId = Member::query()->where('ulid', $data['member_ulid'])->value('id');
        } elseif (filled($email)) {
            $memberId = Member::query()->where('email', $email)->value('id');
        }

        $startDate = filled(Arr::get($data, 'start_date')) ? Carbon::parse($data['start_date']) : Carbon::today();

        return Pledge::create([
            'member_id' => $memberId,
            'name' => $data['name'],
            'email' => $email,
            'phone' => Arr::get($data, 'phone'),
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $startDate,
            'next_due_on' => $startDate,
            'status' => PRFPledgeStatus::ACTIVE->value,
        ]);
    }
}
