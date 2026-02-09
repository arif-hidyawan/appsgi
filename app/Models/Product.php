<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Import HasMany

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        // --- HAPUS CAST HARGA STATIS ---
        // 'cost_price' => 'decimal:2',
        // 'price_1' => 'decimal:2',
        // 'price_2' => 'decimal:2',
        // 'price_3' => 'decimal:2',
    ];

    // Relasi lainnya sudah benar

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // Relasi ke Detail Stok per Gudang
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->item_code)) {
                // Ambil ID terakhir atau urutan terakhir
                $lastProduct = static::orderBy('id', 'desc')->first();
                $lastNumber = $lastProduct ? (int) substr($lastProduct->item_code, 4) : 0;
                
                // Format: ITM-000001
                $product->item_code = 'ITM-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseOrderItems()
{
    return $this->hasMany(PurchaseOrderItem::class);
}

public function salesOrderItems()
{
    return $this->hasMany(SalesOrderItem::class);
}

public function company()
    {
        return $this->belongsTo(Company::class);
    }


    public function getAvailableStock($companyId)
    {
        // 1. Hitung Total Stok Fisik di Company ini (Semua Gudang)
        $physicalStock = $this->stocks()
            ->where('company_id', $companyId)
            ->sum('quantity');
    
        // 2. Hitung Stok yang SEDANG DIKUNCI (Reserved) di SO yang aktif
        $reservedStock = \App\Models\SalesOrderItem::query()
            ->where('product_id', $this->id)
            ->whereNotNull('reserved_at') // Hanya item yang dikunci
            ->whereHas('salesOrder', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  // Pastikan hanya menghitung SO yang masih aktif (belum dikirim/selesai)
                  // Sesuaikan status ini dengan flow bisnis Anda
                  ->whereIn('status', ['New', 'Processed']); 
            })
            ->sum('qty');
    
        // 3. Stok Bebas = Fisik - Bookingan Orang
        return $physicalStock - $reservedStock;
    }

    public function stockClassification(): BelongsTo
{
    return $this->belongsTo(StockClassification::class);
}
    
}