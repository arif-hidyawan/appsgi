<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Hapus hanya muncul jika status masih Draft
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'Draft'),
        ];
    }

    protected function getFormActions(): array
    {
        // Jika status BUKAN 'Draft', hilangkan tombol Simpan, sisakan tombol Kembali
        if ($this->record->status !== 'Draft') {
            return [
                $this->getCancelFormAction()
                    ->label('Kembali'),
            ];
        }

        // Jika status 'Draft', tampilkan tombol Simpan sesuai permintaan
        return [
            // 1. Tombol Simpan
            $this->getSaveFormAction()
                ->label('Simpan'),

            // 2. Tombol Simpan & Keluar
            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
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
        $this->save(shouldRedirect: false);

        $this->redirect($this->getResource()::getUrl('index'));
    }

    /**
     * Redirect setelah klik tombol 'Simpan' utama
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}