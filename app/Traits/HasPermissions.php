<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function hasPermission(string $permission): bool
    {
        // cache per-user permission names for short time
        $cacheKey = "user_permissions_{$this->getKey()}";
        $perms = Cache::remember($cacheKey, 60, function () {
            return $this->permissions()->pluck('name')->all();
        });

        return in_array($permission, $perms, true);
    }

    public function givePermissionTo($permission): void
    {
        $permission = $this->resolvePermission($permission);
        $this->permissions()->syncWithoutDetaching([$permission->id]);
        $this->clearPermissionsCache();
    }

    public function revokePermissionFrom($permission): void
    {
        $permission = $this->resolvePermission($permission);
        $this->permissions()->detach($permission->id);
        $this->clearPermissionsCache();
    }

    protected function resolvePermission($permission)
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        return Permission::firstWhere('name', $permission)
            ?? throw new \InvalidArgumentException("Permission [{$permission}] does not exist.");
    }

    public function clearPermissionsCache(): void
    {
        Cache::forget("user_permissions_{$this->getKey()}");
    }
}
