<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Cek isi gudang ini ada produk apa aja
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }
}