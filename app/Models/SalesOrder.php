<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    //public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function sales(): BelongsTo { return $this->belongsTo(User::class, 'sales_id'); }
    
    public function items(): HasMany { return $this->hasMany(SalesOrderItem::class); }

   // Hapus function quotation() yang lama (belongsTo)
public function quotations()
{
    return $this->belongsToMany(Quotation::class, 'quotation_sales_order');
}

    // 2. Relasi ke Children (Purchase Order)
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // 3. Relasi ke Children (Delivery Order)
    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    // 4. Relasi ke Children (Sales Invoice)
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    // --- RELASI KE USER ---
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // --- AUTO-FILL USER SAAT CREATE/UPDATE ---
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
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function tax()
{
    return $this->belongsTo(\App\Models\Tax::class, 'tax_id');
}

public function invoices()
    {
        // Cari Invoice yang memiliki item dengan sales_order_id milik SO ini
        return SalesInvoice::whereHas('items', function ($query) {
            $query->where('sales_order_id', $this->id);
        })->get();
    }
}