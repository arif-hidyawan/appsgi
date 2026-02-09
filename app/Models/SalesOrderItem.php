<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $guarded = [];

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}