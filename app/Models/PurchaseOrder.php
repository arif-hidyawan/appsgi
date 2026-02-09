<?php

namespace App\Models;

use App\Models\VendorContact;
use App\Models\User;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    // --- RELASI UTAMA ---
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function contact(): BelongsTo { return $this->belongsTo(VendorContact::class, 'vendor_contact_id'); }
    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function goodsReceives(): HasMany { return $this->hasMany(GoodsReceive::class); }

    // --- RELASI AUDIT TRAIL ---
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    // --- HELPER ATTRIBUTE: MENGHITUNG SISA BARANG ---
    // Digunakan untuk mengetahui item mana saja yang belum lunas diterima
    public function getRemainingItemsAttribute()
    {
        // 1. Ambil semua item yang dipesan di PO ini
        $poItems = $this->items;
        
        // 2. Hitung total qty yang SUDAH diterima sebelumnya (dari semua GR terkait PO ini)
        $grIds = $this->goodsReceives()->pluck('id');
        
        $receivedMap = GoodsReceiveItem::whereIn('goods_receive_id', $grIds)
            ->selectRaw('product_id, sum(qty_received) as total_received')
            ->groupBy('product_id')
            ->pluck('total_received', 'product_id'); // [product_id => total_qty]

        $remaining = [];

        // 3. Bandingkan Qty Pesan vs Qty Terima
        foreach ($poItems as $item) {
            $received = $receivedMap[$item->product_id] ?? 0;
            $sisa = $item->qty - $received;
            
            // Hanya masukkan ke list jika masih ada sisa
            if ($sisa > 0) {
                $remaining[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'qty_ordered' => $item->qty,
                    'qty_received_so_far' => $received,
                    'qty_remaining' => $sisa
                ];
            }
        }

        return collect($remaining);
    }

    // --- AUTO FILL LOGIC (AUDIT TRAIL) ---
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

    public function pic()
{
    return $this->belongsTo(User::class, 'pic_id');
}
}