<?php

namespace App\Jobs\Membership;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Member;
use App\Models\Membership;
use App\Models\SpiritualYear;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Membership
    {
        $membership = Membership::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'member_ulid' => Member::class,
            'spiritual_year_ulid' => SpiritualYear::class,
        ]);

        $membership->update($attributes);

        return $membership;
    }
}
