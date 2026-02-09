<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    protected $guarded = [];

    public function deliveryOrder(): BelongsTo { return $this->belongsTo(DeliveryOrder::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    // --- LOGIKA POTONG STOK OTOMATIS ---
    protected static function booted()
    {
        // Saat item dibuat (dikirim), kurangi stok produk
        static::created(function ($item) {
            $product = $item->product;
            if ($product) {
                $product->decrement('stock', $item->qty_delivered);
            }
        });

        // Saat item dihapus (batal kirim), kembalikan stok
        static::deleted(function ($item) {
            $product = $item->product;
            if ($product) {
                $product->increment('stock', $item->qty_delivered);
            }
        });
    }
}