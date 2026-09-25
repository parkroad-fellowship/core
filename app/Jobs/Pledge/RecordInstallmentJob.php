<?php

namespace App\Jobs\Pledge;

use App\Enums\PRFPledgeInstallmentMethod;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class RecordInstallmentJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public ?User $recordedBy = null,
    ) {}

    /**
     * Record a Treasurer-entered fulfillment for a pledge and advance its
     * due-date cadence.
     */
    public function handle(): PledgeInstallment
    {
        $data = $this->data;
        $pledge = Pledge::query()->where('ulid', $data['pledge_ulid'])->firstOrFail();

        $fulfilledOn = filled(Arr::get($data, 'fulfilled_on')) ? Carbon::parse($data['fulfilled_on']) : Carbon::today();

        $installment = PledgeInstallment::create([
            'pledge_id' => $pledge->id,
            'amount' => $data['amount'],
            'fulfilled_on' => $fulfilledOn,
            'method' => PRFPledgeInstallmentMethod::MANUAL->value,
            'notes' => Arr::get($data, 'notes'),
            'recorded_by' => $this->recordedBy?->id,
        ]);

        return $installment;
    }
}
