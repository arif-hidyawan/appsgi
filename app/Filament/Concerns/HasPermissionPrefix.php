<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasPermissionPrefix
{
    /*
    |--------------------------------------------------------------------------
    | Akses Resource berdasarkan Permission
    |--------------------------------------------------------------------------
    |
    | Trait ini otomatis mengecek permission berdasarkan $permissionPrefix
    | yang didefinisikan di Resource.
    | Contoh: jika prefixes = 'item', maka akan cek 'item.view', 'item.create', dll.
    */

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can(static::$permissionPrefix . '.delete') ?? false;
    }
}