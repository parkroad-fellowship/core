<?php

namespace App\Jobs\Member;

use App\Models\Church;
use App\Models\Department;
use App\Models\Gift;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Profession;
use App\Models\SpiritualYear;
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

        $departmentUlids = Arr::pull($data, 'department_ulids', []);
        $giftUlids = Arr::pull($data, 'gift_ulids', []);
        $memberships = Arr::pull($data, 'memberships', []);

        $member = Member::create($data);

        if (! empty($departmentUlids)) {
            $departmentIds = Department::whereIn('ulid', $departmentUlids)->pluck('id');
            $member->departments()->sync($departmentIds);
        }

        if (! empty($giftUlids)) {
            $giftIds = Gift::whereIn('ulid', $giftUlids)->pluck('id');
            $member->gifts()->sync($giftIds);
        }

        foreach ($memberships as $membership) {
            $spiritualYear = SpiritualYear::where('ulid', $membership['spiritual_year_ulid'])->firstOrFail();

            Membership::create([
                'member_id' => $member->id,
                'spiritual_year_id' => $spiritualYear->id,
                'type' => $membership['type'],
                'approved' => $membership['approved'] ?? false,
                'amount' => $membership['amount'] ?? null,
            ]);
        }

        return $member;
    }
}
