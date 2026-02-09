<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    // WAJIB ADA: Agar bisa di-insert menggunakan ::create()
    protected $fillable = [
        'return_number',
        'date',
        'purchase_order_id',
        'vendor_id',
        'company_id',
        'goods_receive_id', // Penting karena kita simpan ID GR asal
        'notes',
        'status',
    ];

    // Relasi
    public function items(): HasMany
    { 
        return $this->hasMany(PurchaseReturnItem::class); 
    }

    public function vendor(): BelongsTo 
    { 
        return $this->belongsTo(Vendor::class); 
    }

    public function purchaseOrder(): BelongsTo 
    { 
        return $this->belongsTo(PurchaseOrder::class); 
    }

    public function company(): BelongsTo 
    { 
        return $this->belongsTo(Company::class); 
    }

    // Tambahan (Optional): Relasi ke Goods Receive biar bisa dilacak balik
    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class);
    }
}