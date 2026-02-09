<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Pastikan import ini ada

class Role extends SpatieRole
{
    // Tambahkan ": BelongsToMany" di sini agar kompatibel dengan Spatie
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            'model_id'
        );
    }
}