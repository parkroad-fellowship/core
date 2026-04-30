<?php

namespace App\Models\Concerns;

trait HasModelPermissions
{
    /**
     * Get the entity name used in permission strings.
     *
     * Auto-derives from class name: MissionOfflineMember → 'mission offline member'.
     * Override in models where the class name doesn't match the config string.
     */
    public static function permissionEntity(): string
    {
        return strtolower(trim(
            preg_replace('/[A-Z]/', ' $0', class_basename(static::class))
        ));
    }

    /**
     * Build a permission string: action + entity.
     *
     * Example: Mission::permission('create') → 'create mission'
     */
    public static function permission(string $action): string
    {
        return $action.' '.static::permissionEntity();
    }
}
