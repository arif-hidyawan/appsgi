<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    protected $table = 'sales_returns';

    protected $fillable = [
        'return_number',
        'date',
        'delivery_order_id',
        'sales_order_id',
        'customer_id',
        'company_id',
        'warehouse_id', // Opsional: Jika ingin mencatat gudang penerima di header
        'reason',
        'status', // Draft, Approved, Rejected
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relasi ke Item Retur
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    /**
     * Relasi ke Surat Jalan Asal (Delivery Order)
     */
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /**
     * Relasi ke Sales Order Asal
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Relasi ke Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke Perusahaan Internal
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relasi untuk Audit Trail (Dibuat Oleh)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi untuk Audit Trail (Diubah Oleh)
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Boot function untuk otomatis isi created_by/updated_by (Opsional, jika tidak pakai Observer)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->created_by && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}