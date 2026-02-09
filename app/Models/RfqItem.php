<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqItem extends Model
{
    protected $guarded = [];

    public function rfq(): BelongsTo { return $this->belongsTo(Rfq::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    
    // Vendor Terpilih (Final)
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }

    // Vendor Opsi
    public function vendor1(): BelongsTo { return $this->belongsTo(Vendor::class, 'vendor_1_id'); }
    public function vendor2(): BelongsTo { return $this->belongsTo(Vendor::class, 'vendor_2_id'); }
    public function vendor3(): BelongsTo { return $this->belongsTo(Vendor::class, 'vendor_3_id'); }
}