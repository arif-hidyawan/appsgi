<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospek extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_visit_date' => 'date',
        'last_order_date' => 'date',
    ];

    public function sales() // Nama fungsi lebih baik 'sales' daripada 'user'
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    // Relasi ke Customer (Pastikan Anda punya model Customer)
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}