<?php

namespace App\Filament\Resources\SalesInvoiceResource\Pages;

use App\Filament\Resources\SalesInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesInvoice extends EditRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Mengatur tombol-tombol aksi di bagian bawah form
     */
    protected function getFormActions(): array
    {
        return [
            // 1. Tombol Simpan (Tetap di halaman ini)
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle'),

            // 2. Tombol Simpan & Keluar (Custom)
            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action('saveAndClose'),

            // 3. Tombol Kembali / Batal
            $this->getCancelFormAction()
                ->label('Kembali'),
        ];
    }

    /**
     * Logic untuk tombol "Simpan & Keluar"
     */
    public function saveAndClose(): void
    {
        // 1. Simpan data (tanpa redirect bawaan)
        $this->save(shouldRedirect: false);

        // 2. Redirect manual ke halaman List (Index)
        $this->redirect($this->getResource()::getUrl('index'));
    }

    /**
     * Redirect default jika tombol "Simpan" biasa yang diklik
     * (Tetap di halaman Edit agar bisa lanjut kerja)
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}