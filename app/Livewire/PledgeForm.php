<?php

namespace App\Livewire;

use App\Enums\PRFPledgeFrequency;
use App\Jobs\Pledge\CreateJob;
use App\Models\Tenant;
use App\Services\Turnstile\TurnstileService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Livewire\Attributes\Rule;
use Livewire\Component;

class PledgeForm extends Component
{
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|email|max:255')]
    public string $email = '';

    #[Rule('nullable|string|max:40')]
    public string $phone = '';

    #[Rule('required|numeric|min:1|max:999999999.99')]
    public string $amount = '';

    #[Rule('required|in:0,1,3,12')]
    public int $frequency = 1;

    #[Rule('nullable|date')]
    public ?string $startDate = null;

    #[Rule('nullable|string|max:2048')]
    public string $turnstileToken = '';

    public bool $submitted = false;

    /** @var array<string, mixed> Summary shown on the confirmation screen. */
    public array $summary = [];

    public function mount(): void
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
    }

    /**
     * Options for the frequency selector, driven by the pledge enum so the
     * form and API stay in sync.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function frequencies(): array
    {
        return [
            ['value' => PRFPledgeFrequency::ONE_TIME->value, 'label' => PRFPledgeFrequency::ONE_TIME->getLabel()],
            ['value' => PRFPledgeFrequency::MONTHLY->value, 'label' => PRFPledgeFrequency::MONTHLY->getLabel()],
            ['value' => PRFPledgeFrequency::QUARTERLY->value, 'label' => PRFPledgeFrequency::QUARTERLY->getLabel()],
            ['value' => PRFPledgeFrequency::YEARLY->value, 'label' => PRFPledgeFrequency::YEARLY->getLabel()],
        ];
    }

    /**
     * Live annual-impact preview while the member types.
     */
    public function impact(): ?string
    {
        $amount = (float) $this->amount;

        if ($amount <= 0) {
            return null;
        }

        if ($this->frequency === PRFPledgeFrequency::ONE_TIME->value) {
            return number_format($amount) . ' once this year';
        }

        return number_format($amount * (12 / $this->frequency)) . ' / year';
    }

    public function submit(): void
    {
        $validated = $this->validate();

        if (app(TurnstileService::class)->isEnabled()) {
            $this->validate([
                'turnstileToken' => app(TurnstileService::class)->fieldRules(),
            ]);

            $validated['turnstileToken'] = $this->turnstileToken;
        }

        $payload = [
            'name' => $validated['name'],
            'amount' => (float) $validated['amount'],
            'frequency' => $validated['frequency'],
            'start_date' => $validated['startDate'] ?? Carbon::today()->format('Y-m-d'),
        ];

        if (filled($validated['email'] ?? null)) {
            $payload['email'] = $validated['email'];
        }

        if (filled($validated['phone'] ?? null)) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $formattedPhone = $phoneUtil->format(
                number: $phoneUtil->parse($validated['phone'], 'KE'),
                numberFormat: PhoneNumberFormat::E164,
            );
            $payload['phone'] = $formattedPhone;
        }

        $memberUlid = Request::query('member_ulid');

        if (filled($memberUlid)) {
            $payload['member_ulid'] = $memberUlid;
        }

        $pledge = $this->runInPledgeTenant(fn() => CreateJob::dispatchSync($payload));

        $this->summary = [
            'name' => $pledge->name,
            'email' => $pledge->email,
            'amount' => number_format((float) $pledge->amount),
            'frequency' => $pledge->frequency?->getLabel(),
            'start_date' => $pledge->start_date?->format('Y-m-d'),
        ];
        $this->submitted = true;
        $this->turnstileToken = '';
        $this->dispatch('turnstile-reset');
    }

    public function startOver(): void
    {
        $this->reset(['name', 'email', 'phone', 'amount', 'turnstileToken']);
        $this->frequency = PRFPledgeFrequency::MONTHLY->value;
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->submitted = false;
        $this->summary = [];
        $this->resetErrorBag();
        $this->dispatch('turnstile-reset');
    }

    /**
     * Run a callback with the pledge tenant resolved so a public, no-login
     * request still writes tenant-scoped data.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function runInPledgeTenant(callable $callback): mixed
    {
        if (!tenancy()->initialized) {
            $tenant = Tenant::query()
                ->where('is_active', true)
                ->whereHas('domains', fn($q) => $q->whereIn('domain', config('prf.giving.pledge_tenant_domains', [
                    'app.parkroadfellowship.org',
                ])))
                ->first() ?? Tenant::query()->where('is_active', true)->first();

            abort_if(!$tenant, 500, 'No tenant configured for pledges.');

            tenancy()->initialize($tenant);

            try {
                return $callback();
            } finally {
                tenancy()->end();
            }
        }

        return $callback();
    }

    public function render()
    {
        return view('livewire.pledge-form');
    }
}
