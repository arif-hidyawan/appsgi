<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SalesInvoice extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'is_dp' => 'boolean',
    ];

    /**
     * Boot logic untuk menangani otomatisasi company_id
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            // 1. Prioritas ambil dari Sales Order jika ada
            if ($model->sales_order_id) {
                $so = SalesOrder::find($model->sales_order_id);
                if ($so) {
                    $model->company_id = $so->company_id;
                }
            }
            
            // 2. Fallback: Jika masih kosong, ambil dari user yang sedang login
            if (empty($model->company_id) && Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(SalesInvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(SalesPayment::class); }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}