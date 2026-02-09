<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    protected $table = 'sales_return_items';

    protected $fillable = [
        'sales_return_id',
        'product_id',
        'qty',
        'condition', // 'Good' atau 'Bad'
    ];

    /**
     * Relasi ke Header Sales Return
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * Relasi ke Produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}