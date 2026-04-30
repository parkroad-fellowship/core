<?php

namespace App\Jobs\Membership;

use App\Models\Member;
use App\Models\Membership;
use App\Models\SpiritualYear;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $update = $this->data;

        if (isset($update['member_ulid'])) {
            $member = Member::query()->where('ulid', $update['member_ulid'])->firstOrFail();
            $update['member_id'] = $member->id;
            unset($update['member_ulid']);
        }

        if (isset($update['spiritual_year_ulid'])) {
            $spiritualYear = SpiritualYear::query()->where('ulid', $update['spiritual_year_ulid'])->firstOrFail();
            $update['spiritual_year_id'] = $spiritualYear->id;
            unset($update['spiritual_year_ulid']);
        }

        Membership::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($update);
    }
}
