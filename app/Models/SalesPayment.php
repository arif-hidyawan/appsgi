<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SalesPayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relasi ke Invoice Penjualan
     */
    public function invoice(): BelongsTo 
    { 
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); 
    }

    /**
     * Relasi ke Perusahaan
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
{
    return $this->belongsTo(\App\Models\Customer::class);
}

    /**
     * Relasi ke Akun Kas/Bank penampung dana
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * LOGIKA OTOMATIS: Update Status Invoice, Jurnal Akuntansi, & Status Sales Order
     */
    protected static function booted()
    {
        // 1. Saat pembayaran sedang dibuat
        static::creating(function ($payment) {
            if ($payment->invoice && empty($payment->company_id)) {
                $payment->company_id = $payment->invoice->company_id;
            }
        });

        // 2. Saat pembayaran berhasil disimpan (Create/Update)
        static::saved(function ($payment) {
            $invoice = $payment->invoice;
            if ($invoice) {
                // --- A. UPDATE STATUS INVOICE ---
                $totalPaid = $invoice->payments()->sum('amount');
                
                if ($totalPaid >= $invoice->grand_total) {
                    $invoice->update(['status' => 'Paid']);
                    
                    // --- B. TOTALITAS: UPDATE STATUS SALES ORDER KE LUNAS ---
                    if ($invoice->salesOrder) {
                        $invoice->salesOrder->update(['status' => 'Paid']);
                    }
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'Partial']);
                } else {
                    $invoice->update(['status' => 'Unpaid']);
                }

                // --- C. JURNAL AKUNTANSI OTOMATIS ---
                DB::transaction(function () use ($payment, $invoice) {
                    $arAccount = Account::where('company_id', $payment->company_id)
                        ->where('code', '1-1210')->first();

                    if ($arAccount && $payment->account_id) {
                        $journal = Journal::updateOrCreate(
                            ['reference' => $payment->payment_number],
                            [
                                'company_id'   => $payment->company_id,
                                'journal_date' => $payment->date,
                                'source'       => 'Sales Payment',
                                'memo'         => "Penerimaan Piutang - {$invoice->customer->name} (Inv: {$invoice->invoice_number})",
                            ]
                        );

                        $journal->lines()->delete();

                        $journal->lines()->create([
                            'account_id' => $payment->account_id,
                            'direction'  => 'debit',
                            'amount'     => $payment->amount,
                            'note'       => "Penerimaan pembayaran {$payment->payment_method}",
                        ]);

                        $journal->lines()->create([
                            'account_id' => $arAccount->id,
                            'direction'  => 'credit',
                            'amount'     => $payment->amount,
                            'note'       => "Pelunasan atas tagihan {$invoice->invoice_number}",
                        ]);
                    }
                });
            }
        });

        // 3. Saat pembayaran dihapus
        static::deleted(function ($payment) {
            Journal::where('reference', $payment->payment_number)->delete();

            $invoice = $payment->invoice;
            if ($invoice) {
                $totalPaid = $invoice->payments()->sum('amount');
                
                if ($totalPaid < $invoice->grand_total) {
                    $invoice->update(['status' => $totalPaid <= 0 ? 'Unpaid' : 'Partial']);
                    
                    // ROLLBACK STATUS SO JIKA TIDAK LAGI LUNAS
                    if ($invoice->salesOrder) {
                        $invoice->salesOrder->update(['status' => 'Invoiced']);
                    }
                }
            }
        });
    }
}