<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberingTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'source',
        'format',
        'pad_length',
        'last_sequence',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}