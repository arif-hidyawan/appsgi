<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PurchasePayment extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date'];

    public function invoice(): BelongsTo 
    { 
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id'); 
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor()
{
    return $this->belongsTo(\App\Models\Vendor::class);
}

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            if ($payment->invoice && empty($payment->company_id)) {
                $payment->company_id = $payment->invoice->company_id;
            }
        });

        static::saved(function ($payment) {
            $invoice = $payment->invoice;
            if ($invoice) {
                // 1. Update Status Invoice
                $totalPaid = $invoice->payments()->sum('amount');
                if ($totalPaid >= $invoice->grand_total) {
                    $invoice->update(['status' => 'Paid']);

                    // --- TOTALITAS: UPDATE STATUS PO KE LUNAS ---
                    if ($invoice->purchaseOrder) {
                        // Pastikan di PurchaseOrderResource opsi 'Paid' sudah ada
                        $invoice->purchaseOrder->update(['status' => 'Paid']);
                    }
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'Partial']);
                } else {
                    $invoice->update(['status' => 'Unpaid']);
                }

                // 2. JURNAL OTOMATIS
                DB::transaction(function () use ($payment, $invoice) {
                    // Cari Akun Hutang Usaha (2-1101)
                    $apAccount = Account::where('company_id', $payment->company_id)
                        ->where('code', '2-1101')->first();

                    if ($apAccount && $payment->account_id) {
                        // Gunakan updateOrCreate untuk mencegah duplikasi jika saved terpanggil dua kali
                        $journal = Journal::updateOrCreate(
                            ['reference' => $payment->payment_number],
                            [
                                'company_id'   => $payment->company_id,
                                'journal_date' => $payment->date,
                                'source'       => 'Purchase Payment',
                                'memo'         => "Pelunasan Hutang ke {$invoice->vendor->name} (Inv: {$invoice->invoice_number})",
                            ]
                        );

                        // Refresh lines agar tidak double
                        $journal->lines()->delete();

                        // Baris 1: DEBIT Hutang Usaha (Hutang Berkurang)
                        $journal->lines()->create([
                            'account_id' => $apAccount->id,
                            'direction'  => 'debit',
                            'amount'     => $payment->amount,
                            'note'       => "Pelunasan Invoice {$invoice->invoice_number}",
                        ]);

                        // Baris 2: KREDIT Kas/Bank (Uang Berkurang)
                        $journal->lines()->create([
                            'account_id' => $payment->account_id,
                            'direction'  => 'credit',
                            'amount'     => $payment->amount,
                            'note'       => "Pembayaran via {$payment->payment_method}",
                        ]);
                    }
                });
            }
        });

        static::deleted(function ($payment) {
            // Hapus Jurnal terkait jika pembayaran dihapus
            Journal::where('reference', $payment->payment_number)->delete();
            
            $invoice = $payment->invoice;
            if ($invoice) {
                $totalPaid = $invoice->payments()->sum('amount');
                
                // Update status invoice
                if ($totalPaid >= $invoice->grand_total) {
                    $invoice->update(['status' => 'Paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'Partial']);
                } else {
                    $invoice->update(['status' => 'Unpaid']);
                }

                // ROLLBACK STATUS PO JIKA TIDAK LAGI LUNAS
                if ($totalPaid < $invoice->grand_total && $invoice->purchaseOrder) {
                    // Kembalikan ke 'Billed' (Tagihan Diterima)
                    $invoice->purchaseOrder->update(['status' => 'Billed']);
                }
            }
        });
    }
}