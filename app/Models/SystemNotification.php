<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemNotification extends Model
{
    use HasFactory;

    // Paksa arahkan ke tabel notifications
    protected $table = 'notifications';

    // Matikan incrementing karena ID-nya UUID (String)
    public $incrementing = false;
    protected $keyType = 'string';

    // Izinkan semua kolom diisi
    protected $guarded = [];

    // Cast data JSON agar bisa dibaca Filament
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}