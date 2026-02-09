<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'accounts';

    protected $fillable = [
        'company_id', // <--- WAJIB DITAMBAHKAN BIAR GAK ERROR SAAT SAVE
        'code',
        'name',
        'type', 
        'nature', 
        'parent_id',
        'is_cash_bank',
        'is_inventory',
        'is_active'
    ];

    protected $casts = [
        'is_cash_bank' => 'boolean',
        'is_inventory' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relasi ke Perusahaan (BARU)
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Relasi ke Diri Sendiri (Induk)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    // Relasi ke Diri Sendiri (Anak/Sub-akun)
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    // Relasi ke Baris Jurnal
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}