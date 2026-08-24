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

        $permissionsByName = [];
        foreach ($permissionsByRole as $roleName => $permissions) {
            foreach ($permissions as $permissionName) {
                $permissionsByName[$permissionName] = Permission::query()->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

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
