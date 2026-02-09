<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $guarded = [];

    public function purchaseInvoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}