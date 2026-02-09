<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

// PENTING: Extend SpatiePermission
class Permission extends SpatiePermission
{
    // protected $fillable = ['name', 'guard_name'];
}