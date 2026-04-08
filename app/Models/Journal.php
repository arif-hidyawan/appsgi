<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Journal extends Model
{
    protected $table = 'journals';

    protected $fillable = [
        'company_id',
        'journal_date',
        'memo',
        'source',
        'reference',
        'bkk_number', 
        'pic_id',     
        'sales_name', 
        'contact_name',
        'is_real',
        'expense_category',
        'posted_by',
        'posted_at'
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
        'is_real' => 'boolean',
    ];

    /**
     * LOGIC AUTO-NUMBERING
     */
    protected static function boot()
    {
        parent::boot();

        // Trigger saat pertama kali dibuat
        static::creating(function ($journal) {
            static::generateAutoNumber($journal);
        });

        // Trigger saat diupdate (jika nomor masih kosong)
        static::updating(function ($journal) {
            static::generateAutoNumber($journal);
        });
    }

    /**
     * Fungsi Helper untuk Generate Nomor Berdasarkan Template
     */
    protected static function generateAutoNumber($journal)
    {
        // Hanya generate jika bkk_number MASIH KOSONG
        if (empty($journal->bkk_number) && !empty($journal->source)) {
            
            $template = NumberingTemplate::where('company_id', $journal->company_id)
                ->where('source', $journal->source)
                ->first();
            
            if ($template) {
                // Tambah sequence
                $template->increment('last_sequence');
                
                $date = $journal->journal_date ? Carbon::parse($journal->journal_date) : now();
                $num = str_pad($template->last_sequence, $template->pad_length, '0', STR_PAD_LEFT);
                
                $generatedNumber = str_replace(
                    ['{m}', '{y}', '{Y}', '{num}'],
                    [$date->format('m'), $date->format('y'), $date->format('Y'), $num],
                    $template->format
                );
                
                $journal->bkk_number = $generatedNumber;
            }
        }
    }

    // --- RELATIONS ---

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // --- ACCESSORS ---
    
    public function getTotalDebitAttribute()
    {
        return $this->lines->where('direction', 'debit')->sum('amount');
    }

    public function getTotalCreditAttribute()
    {
        return $this->lines->where('direction', 'credit')->sum('amount');
    }
}