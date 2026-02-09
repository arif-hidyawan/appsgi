<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    public function rfqs()
{
    return $this->belongsToMany(Rfq::class, 'quotation_rfq');
}
    public function items(): HasMany { return $this->hasMany(QuotationItem::class); }

    // Tambahkan Relasi
public function contact()
{
    return $this->belongsTo(CustomerContact::class, 'customer_contact_id');
}

// --- 1. RELASI KE USER ---
public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updater(): BelongsTo
{
    return $this->belongsTo(User::class, 'updated_by');
}

// --- 2. LOGIC OTOMATIS ISI USER SAAT CREATE/UPDATE ---
protected static function booted(): void
{
    static::creating(function ($model) {
        if (auth()->check()) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        }
    });

    static::updating(function ($model) {
        if (auth()->check()) {
            $model->updated_by = auth()->id();
        }
    });
}

public function company()
{
    return $this->belongsTo(Company::class);
}

public function salesOrders()
{
    return $this->belongsToMany(SalesOrder::class, 'quotation_sales_order');
}

}