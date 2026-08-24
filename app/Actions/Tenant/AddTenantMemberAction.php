<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\User;

final class AddTenantMemberAction
{
    public function handle(Tenant $tenant, User $user, string $role = 'member'): void
    {
        $tenant
            ->members()
            ->syncWithoutDetaching([
                $user->getKey() => ['role' => $role],
            ]);
    }
}
