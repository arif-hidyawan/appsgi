<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    protected $guarded = [];

    public function salesInvoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
}