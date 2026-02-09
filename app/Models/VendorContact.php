<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorContact extends Model
{
    // Mengizinkan semua kolom diisi (termasuk pic_name, birth_date, dll)
    protected $guarded = []; 

    protected $casts = [
        'category' => 'array',  // Wajib agar JSON terbaca sebagai Array di PHP
        'birth_date' => 'date', // Wajib agar DatePicker Filament bekerja
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}