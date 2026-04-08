<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryCustomer extends Model
{
    use HasFactory;

    protected $table = 'temporary_customers';

    protected $fillable = [
        'company_name',
        'email',
        'business_type',
        'address',
        'district',
        'city',
        'province',
        'company_phone',
        'pic_name',
        'pic_position',
        'pic_phone',
        'business_scope',
        'payment_terms',
        'supporting_documents',
        'status',
    ];

    // Beritahu Laravel bahwa kolom ini berisi JSON/Array
    protected $casts = [
        'supporting_documents' => 'array',
    ];
}