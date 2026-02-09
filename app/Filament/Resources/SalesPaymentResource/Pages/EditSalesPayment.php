<?php

namespace App\Filament\Resources\SalesPaymentResource\Pages;

use App\Filament\Resources\SalesPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Journal;
use App\Models\Account;
use Filament\Notifications\Notification;

class EditSalesPayment extends EditRecord
{
    protected static string $resource = SalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Logic saat Pembayaran Dihapus
            Actions\DeleteAction::make()
                ->after(function ($record) {
                    // 1. Hapus Jurnal terkait
                    Journal::where('source', 'Sales Payment')
                        ->where('reference', $record->payment_number)
                        ->delete();

                    // 2. Kembalikan Status Invoice
                    $invoice = $record->invoice;
                    if ($invoice) {
                        $invoice->refresh();
                        $totalPaid = $invoice->payments()->sum('amount');

                        if ($totalPaid == 0) {
                            $invoice->update(['status' => 'Unpaid']);
                        } elseif ($totalPaid < $invoice->grand_total) {
                            $invoice->update(['status' => 'Partial']);
                        }
                    }
                    
                    Notification::make()->title('Pembayaran Dihapus & Jurnal Dibatalkan')->warning()->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Logic Update Otomatis (Jurnal & Status Invoice) setelah Edit Disimpan
     */
    protected function afterSave(): void
    {
        $payment = $this->record;
        $invoice = $payment->invoice;

        // --- 1. UPDATE JURNAL AKUNTANSI ---
        // Cari jurnal berdasarkan referensi Payment Number
        $journal = Journal::where('source', 'Sales Payment')
            ->where('reference', $payment->payment_number)
            ->first();

        $arAccount = Account::where('company_id', $payment->company_id)
            ->where('code', '1-1210')
            ->first();

        if ($journal && $arAccount && $payment->account_id) {
            // Update Header Jurnal (Tanggal mungkin berubah)
            $journal->update([
                'journal_date' => $payment->date,
                'memo' => "Penerimaan Pembayaran Invoice {$invoice->invoice_number} (Revisi)",
            ]);

            // Hapus detail jurnal lama, ganti dengan angka baru (Clean approach)
            $journal->lines()->delete();

            // Create Ulang Debit: Kas/Bank
            $journal->lines()->create([
                'account_id' => $payment->account_id,
                'direction'  => 'debit',
                'amount'     => $payment->amount,
                'note'       => 'Penerimaan Kas/Bank (Revisi)',
            ]);

            // Create Ulang Kredit: Piutang
            $journal->lines()->create([
                'account_id' => $arAccount->id,
                'direction'  => 'credit',
                'amount'     => $payment->amount,
                'note'       => 'Pelunasan Piutang (Revisi)',
            ]);
        }

        // --- 2. RECALCULATE STATUS INVOICE ---
        if ($invoice) {
            $invoice->refresh(); // Ambil data terbaru termasuk payment yg baru diedit
            $totalPaid = $invoice->payments()->sum('amount');

            // Cek apakah masih Lunas atau turun jadi Partial
            if ($totalPaid >= $invoice->grand_total) {
                if ($invoice->status !== 'Paid') {
                    $invoice->update(['status' => 'Paid']);
                    $invoice->salesOrder?->update(['status' => 'Paid']);
                }
            } else {
                // Jika setelah diedit jumlahnya berkurang dan jadi belum lunas
                $invoice->update(['status' => 'Partial']);
                // Sales order status opsional mau dibalikkan atau tidak
            }
        }

        Notification::make()->title('Data Pembayaran & Jurnal Diperbarui')->success()->send();
    }
}