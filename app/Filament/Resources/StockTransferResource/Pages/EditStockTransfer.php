<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockTransfer extends EditRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // PERBAIKAN: Ganti 'New' jadi 'Draft'
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'Draft'),
        ];
    }

    protected function getFormActions(): array
    {
        // PERBAIKAN: Ganti 'New' jadi 'Draft'
        // Jika status BUKAN 'Draft', sembunyikan tombol simpan
        if ($this->record->status !== 'Draft') {
            return [
                $this->getCancelFormAction()
                    ->label('Kembali'),
            ];
        }

        // Jika status masih 'Draft', tampilkan tombol simpan
        return [
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle'),

            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action('saveAndClose'),

            $this->getCancelFormAction()
                ->label('Kembali'),
        ];
    }

    public function saveAndClose(): void
    {
        $this->save(shouldRedirect: false);
        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}