<?php

namespace App\Models\Concerns;

use App\Models\ConnectedAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property Collection $connectedAccounts
 */
trait HasConnectedAccounts
{
    public function ownsConnectedAccount(mixed $connectedAccount): bool
    {
        return $this->id == optional($connectedAccount)->user_id;
    }

    public function hasTokenFor(string $provider): bool
    {
        return $this->connectedAccounts->contains('provider', Str::lower($provider));
    }

    public function getTokenFor(string $provider, mixed $default = null): mixed
    {
        if ($this->hasTokenFor($provider)) {
            return $this->connectedAccounts
                ->where('provider', Str::lower($provider))
                ->first()
                ->token;
        }

        return $default;
    }

    public function getConnectedAccountFor(string $provider, string $id): mixed
    {
        return $this->connectedAccounts
            ->where('provider', $provider)
            ->where('provider_id', $id)
            ->first();
    }

    public function connectedAccounts(): HasMany
    {
        return $this->hasMany(ConnectedAccount::class, 'user_id', $this->getAuthIdentifierName());
    }
}
