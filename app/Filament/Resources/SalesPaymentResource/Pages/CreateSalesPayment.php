<?php

namespace App\Filament\Resources\SalesPaymentResource\Pages;

use App\Filament\Resources\SalesPaymentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Account;
use App\Models\Journal;
use Filament\Notifications\Notification;

class CreateSalesPayment extends CreateRecord
{
    protected static string $resource = SalesPaymentResource::class;

    /**
     * Redirect ke halaman Index setelah berhasil simpan
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Logic Otomatis Setelah Data Pembayaran Tersimpan di Database
     */
    protected function afterCreate(): void
    {
        // 1. Ambil data payment yang baru saja dibuat
        $payment = $this->record;
        
        // 2. Ambil Invoice terkait
        $invoice = $payment->invoice;

        if ($invoice) {
            // Hitung total yang sudah dibayar (termasuk payment ini)
            $totalPaid = $invoice->payments()->sum('amount');

            // Cek Lunas atau Belum
            if ($totalPaid >= $invoice->grand_total) {
                // Update Invoice jadi PAID
                $invoice->update(['status' => 'Paid']);
                
                // Update Sales Order jadi PAID juga (jika ada relasinya)
                $invoice->salesOrder?->update(['status' => 'Paid']);
                
                Notification::make()->title('Status Invoice Berubah: LUNAS')->success()->send();
            } else {
                // Update Invoice jadi PARTIAL
                $invoice->update(['status' => 'Partial']);
            }

            // 3. Buat Jurnal Otomatis (Double Entry)
            // Cari Akun Piutang (AR) milik perusahaan ini
            $arAccount = Account::where('company_id', $payment->company_id)
                ->where('code', '1-1210') // Kode Akun Piutang Usaha
                ->first(); 

            // Pastikan Akun Bank (Tujuan) & Akun Piutang ada
            if ($arAccount && $payment->account_id) {
                
                $journal = Journal::create([
                    'company_id'   => $payment->company_id,
                    'journal_date' => $payment->date,
                    'reference'    => $payment->payment_number,
                    'source'       => 'Sales Payment', // Sumber transaksi
                    'memo'         => "Penerimaan Pembayaran Invoice {$invoice->invoice_number} - {$invoice->customer->name}",
                ]);

                // A. DEBIT: Kas/Bank (Uang Masuk)
                $journal->lines()->create([
                    'account_id' => $payment->account_id, // Akun Bank yang dipilih di form
                    'direction'  => 'debit',
                    'amount'     => $payment->amount,
                    'note'       => 'Penerimaan Kas/Bank',
                ]);

                // B. KREDIT: Piutang Usaha (Mengurangi Tagihan)
                $journal->lines()->create([
                    'account_id' => $arAccount->id,
                    'direction'  => 'credit',
                    'amount'     => $payment->amount,
                    'note'       => 'Pelunasan Piutang',
                ]);
            }
        }
    }
}