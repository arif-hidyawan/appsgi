<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke Item Transfer
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    // Relasi Asal
    public function sourceCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'source_company_id');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    // Relasi Tujuan
    public function destinationCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'destination_company_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    // Audit Trail
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}