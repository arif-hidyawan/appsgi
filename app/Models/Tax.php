<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'rate' => 'decimal:2',
    ];
}