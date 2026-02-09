<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * TOTALITAS: Booted method untuk menjamin company_id terisi secara otomatis
     * baik saat dibuat via action (dari PO) maupun manual.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            // 1. Jika dibuat dari Purchase Order, ambil company_id dari PO tersebut
            if (empty($model->company_id) && $model->purchase_order_id) {
                $po = PurchaseOrder::find($model->purchase_order_id);
                if ($po) {
                    $model->company_id = $po->company_id;
                }
            }

            // 2. Fallback: Jika masih kosong, ambil dari user yang sedang login (jika ada kolom company_id di user)
            // Atau jika sistem Many-to-Many, pastikan company_id sudah dilempar dari Resource/Action.
            if (empty($model->company_id) && Auth::check()) {
                // Mengambil ID perusahaan pertama yang dimiliki user sebagai default jika belum ditentukan
                $model->company_id = $model->company_id ?? Auth::user()->companies()->first()?->id;
            }
        });
    }

    /**
     * Relasi ke Perusahaan
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo 
    { 
        return $this->belongsTo(PurchaseOrder::class); 
    }

    public function vendor(): BelongsTo 
    { 
        return $this->belongsTo(Vendor::class); 
    }

    public function items(): HasMany 
    { 
        return $this->hasMany(PurchaseInvoiceItem::class); 
    }

    public function payments(): HasMany 
    { 
        return $this->hasMany(PurchasePayment::class); 
    }
}