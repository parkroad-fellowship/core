<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $permissionsByRole = config('prf.roles.roles');

        $insertPermissions = fn ($role) => collect($permissionsByRole[$role])
            ->map(function ($name) {
                $existing = DB::table('permissions')->where('name', $name)->first();
                if ($existing) {
                    return $existing->id;
                }

                return DB::table('permissions')->insertGetId(['name' => $name, 'guard_name' => 'web']);
            })->toArray();

        $permissionIdsByRole = [];
        foreach ($permissionsByRole as $roleName => $permissions) {
            $permissionIdsByRole[$roleName] = $insertPermissions($roleName);
        }

        foreach ($permissionIdsByRole as $role => $permissionIds) {
            // Restore if deleted
            Role::withTrashed()->firstOrCreate(['name' => $role], ['name' => $role])->restore();
            $role = Role::where('name', $role)->first();
            $role->permissions()->detach();
            DB::table('role_has_permissions')
                ->insert(
                    collect($permissionIds)->unique()->map(fn ($id) => [
                        'role_id' => $role->id,
                        'permission_id' => $id,
                    ])->toArray()
                );
        }

        // Mark old roles as deleted to prevent them from being used
        $finalRoles = collect($permissionIdsByRole)->keys()->toArray();
        $missingRoles = Role::whereNotIn('name', $finalRoles)->get();
        $missingRoles->each->delete();
    }
}
