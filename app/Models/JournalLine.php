<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'direction',
        'amount',
        'note'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relasi ke Header (Yang punya Company ID)
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    // Relasi ke Akun
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}