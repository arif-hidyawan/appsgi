<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // 1. Import ini

class Customer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'anniversary_date' => 'date', 
        'supporting_documents' => 'array',
    ];

    // Relasi ke Master Pajak (Default Tax)
    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    // 3. Relasi ke Kontak (PIC)
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function salesOrders()
{
    return $this->hasMany(SalesOrder::class);
}
}