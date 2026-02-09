<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory;
    protected $guarded = []; 

    // --- TAMBAHAN PENTING ---
    // Casting ini mengubah data di database menjadi objek tanggal (Carbon)
    // agar DatePicker Filament bisa membacanya dengan benar.
    protected $casts = [
        'company_anniversary' => 'date',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    public function purchaseOrders()
{
    return $this->hasMany(PurchaseOrder::class);
}
}