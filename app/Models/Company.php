<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'code', // Tambahkan ini
        'name',
        'email',
        'phone',
        'website',
        'address',
        'tax_id',
        'logo',
    ];

    public function users()
    {
        // Relasi Many-to-Many ke User via tabel pivot 'company_user'
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id');
    }
}
