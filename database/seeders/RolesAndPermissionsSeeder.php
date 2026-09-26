<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'tenant_id');
        $teamId = tenancy()->initialized ? tenant('id') : null;

        $permissionRegistrar->setPermissionsTeamId($teamId);

        $permissionsByRole = config('prf.roles.roles');

        // Permissions are shared by every tenant: add the missing ones in bulk, then load them all
        // at once (thousands of firstOrCreate round trips made provisioning and tests slow).
        $names = collect($permissionsByRole)->flatten()->unique()->values();
        $now = now();

        $names->chunk(500)->each(fn($chunk) => Permission::query()->insertOrIgnore(
            $chunk->map(fn(string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all(),
        ));

        $permissionsByName = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names->all())
            ->get()
            ->keyBy('name')
            ->all();

        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $roleAttributes = [
                'name' => $roleName,
                'guard_name' => 'web',
                $teamForeignKey => $teamId,
            ];

            $role = Role::withTrashed()->firstOrCreate($roleAttributes, $roleAttributes);
            $role->restore();

            $role->syncPermissions(
                collect($permissionNames)
                    ->unique()
                    ->map(fn(string $permissionName) => $permissionsByName[$permissionName])
                    ->values(),
            );
        }

        $finalRoles = array_keys($permissionsByRole);
        $missingRoles = Role::query()->where($teamForeignKey, $teamId)->whereNotIn('name', $finalRoles)->get();

        $missingRoles->each->delete();

        $permissionRegistrar->forgetCachedPermissions();
    }
}
