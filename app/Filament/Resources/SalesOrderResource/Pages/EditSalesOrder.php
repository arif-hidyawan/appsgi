<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Hapus di pojok kanan atas HANYA muncul jika status 'New'
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'New'),
        ];
    }

    protected function getFormActions(): array
    {
        // Jika status BUKAN 'New', kita hanya tampilkan tombol 'Kembali'
        if ($this->record->status !== 'New') {
            return [

                $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle'),

            // 2. Tombol Simpan & Keluar
            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action('saveAndClose'),
                
                $this->getCancelFormAction()
                    ->label('Kembali'),
            ];
        }

        // Jika status masih 'New', tampilkan semua tombol aksi simpan
        return [
            // 1. Tombol Simpan (Tetap di halaman ini)
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle'),

            // 2. Tombol Simpan & Keluar
            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action('saveAndClose'),

            // 3. Tombol Kembali
            $this->getCancelFormAction()
                ->label('Kembali'),
        ];
    }

    /**
     * Logika untuk aksi Simpan & Keluar
     */
    public function saveAndClose(): void
    {
        // Menjalankan proses simpan bawaan Filament
        $this->save(shouldRedirect: false);

        // Redirect manual ke halaman index resource
        $this->redirect($this->getResource()::getUrl('index'));
    }

    /**
     * Redirect setelah klik tombol 'Simpan' utama
     */
    protected function getRedirectUrl(): string
    {
        // Tetap di halaman edit setelah klik 'Simpan'
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    /**
     * LOGIC PENTING: Hitung Ulang Pajak & Total setelah Simpan
     * Ini akan dijalankan setiap kali tombol Simpan / Simpan & Keluar diklik.
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // 1. Refresh data & Load relasi penting
        $record->refresh();
        $record->load(['items', 'tax']); // Load item & pajak yang dipilih di header

        // 2. Hitung Subtotal (Total murni dari item barang)
        $subtotal = $record->items->sum('subtotal');

        // 3. Ambil Rate Pajak berdasarkan Tax ID yang dipilih di Header Form
        // Jika tidak ada tax_id, rate dianggap 0
        $taxRate = $record->tax?->rate ?? 0;

        // 4. Hitung Nominal Pajak
        $taxAmount = 0;
        if ($taxRate > 0) {
            $taxAmount = $subtotal * ($taxRate / 100);
        }

        // 5. Hitung Grand Total
        $grandTotal = $subtotal + $taxAmount;

        // 6. Update Database secara "diam-diam" (Quietly)
        // updateQuietly mencegah looping event (jika ada observer lain)
        $record->updateQuietly([
            'subtotal_amount' => $subtotal,
            'tax_amount'      => $taxAmount,
            'grand_total'     => $grandTotal
        ]);
    }
}