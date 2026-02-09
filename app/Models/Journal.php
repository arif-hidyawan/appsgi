<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    protected $table = 'journals';

    protected $fillable = [
        'company_id',
        'journal_date',
        'memo',
        'source',
        'reference',
        'posted_by',
        'posted_at'
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
    ];

    // Relasi ke Detail Baris
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    // Relasi ke User (Posting By) - Optional jika ada tabel users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // --- Helper untuk Hitung Total (Dipakai di Tabel Filament) ---
    
    public function getTotalDebitAttribute()
    {
        return $this->lines->where('direction', 'debit')->sum('amount');
    }

    public function getTotalCreditAttribute()
    {
        return $this->lines->where('direction', 'credit')->sum('amount');
    }

    public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}
}