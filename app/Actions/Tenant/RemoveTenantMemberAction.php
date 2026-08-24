<?php

namespace App\Actions\Tenant;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\User;

final class RemoveTenantMemberAction
{
    public function handle(Tenant $tenant, User $user): void
    {
        $tenant->members()->detach($user->getKey());

        PersonalAccessToken::query()->where('tokenable_id', $user->getKey())->where('tenant_id', $tenant->id)->delete();
    }
}
