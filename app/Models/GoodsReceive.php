<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceive extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function items(): HasMany { return $this->hasMany(GoodsReceiveItem::class); }

    // --- PERBAIKAN DI SINI ---
    // Ubah nama method dari 'contact' menjadi 'vendorContact'
    public function vendorContact(): BelongsTo 
    { 
        return $this->belongsTo(VendorContact::class, 'vendor_contact_id'); 
    }
    
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); } // Pastikan ini ada juga

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
}