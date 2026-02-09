<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rfq extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attachment' => 'array',
        'date' => 'date',
        'deadline' => 'date',
    ];
    
    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // --- TAMBAHAN RELASI SALES ---
    public function sales(): BelongsTo
    {
        // Asumsi sales staff diambil dari tabel User
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function quotations()
    {
        return $this->belongsToMany(Quotation::class, 'quotation_rfq');
    }

public function creator()
{
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}

public function updater()
{
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
}

public function contact()
{
    return $this->belongsTo(CustomerContact::class, 'customer_contact_id');
}

public function company()
{
    return $this->belongsTo(Company::class);
}

public function purchasing()
{
    return $this->belongsTo(User::class, 'purchasing_id');
}

}