<?php

namespace App\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;

trait EnforcesTenantScope
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super admin')) {
            return true;
        }

        return null;
    }

    protected function isTenantMatch(Model $model): bool
    {
        if (!isset($model->tenant_id)) {
            return false;
        }

        return (string) $model->tenant_id === (string) tenant('id');
    }
}
