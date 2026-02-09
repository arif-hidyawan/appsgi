<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiveItem extends Model
{
    protected $guarded = [];

    public function goodsReceive(): BelongsTo { return $this->belongsTo(GoodsReceive::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

}