<?php

namespace App\Filament\Resources\ProspekResource\Pages;

use App\Filament\Resources\ProspekResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProspek extends CreateRecord
{
    protected static string $resource = ProspekResource::class;

    // KITA ATUR URUTAN & LABEL TOMBOL DI SINI
    protected function getFormActions(): array
    {
        return [
            // 1. Tombol "Simpan" (Aslinya: Simpan & Buat Lagi)
            $this->getCreateAnotherFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-plus')->color('primary'),
                 // Opsional: bedakan warnanya jika mau

            // 2. Tombol "Simpan & Tutup" (Aslinya: Simpan)
            $this->getCreateFormAction()
                ->label('Simpan & Tutup')
                ->icon('heroicon-m-check')->color('gray'),

            // 3. Tombol "Batal"
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
    
    // Redirect ke Index setelah "Simpan & Tutup"
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}