<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockClassification extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}