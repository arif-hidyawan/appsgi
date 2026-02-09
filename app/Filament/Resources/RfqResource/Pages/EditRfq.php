<?php

namespace App\Filament\Resources\RfqResource\Pages;

use App\Filament\Resources\RfqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRfq extends EditRecord
{
    protected static string $resource = RfqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                // Sembunyikan tombol Hapus di Header jika status sudah final
                ->hidden(fn ($record) => in_array($record->status, ['Disetujui', 'Selesai'])),
        ];
    }

    // --- KODINGAN BARU DI SINI ---
    protected function getFormActions(): array
    {
        return [
            // 1. TOMBOL SIMPAN
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle')
                ->hidden(fn () => in_array($this->getRecord()->status, ['Disetujui', 'Selesai'])),

            // 2. TOMBOL SIMPAN & KELUAR
            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action(function () {
                    $this->save();
                    return redirect($this->getResource()::getUrl('index'));
                })
                ->hidden(fn () => in_array($this->getRecord()->status, ['Disetujui', 'Selesai'])),

            // 3. TOMBOL KEMBALI (Modifikasi dari tombol Batal)
            $this->getCancelFormAction()
                ->label('Kembali') // Ubah tulisan 'Cancel' jadi 'Kembali'
                ->color('gray'),
        ];
    }
    
    // Opsional: Agar tombol "Simpan" bawaan (yang pertama) redirect ke index juga (jika diinginkan)
    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    protected function mutateFormDataBeforeSave(array $data): array
{
    $data['updated_by'] = auth()->id();
    return $data;
}
}