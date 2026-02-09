<?php

namespace App\Traits;

use Filament\Actions\Action;

trait HasEditFormActions
{
    protected function getFormActions(): array
    {
        return [
            // 1. TOMBOL SIMPAN
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle'),

            // 2. TOMBOL SIMPAN & TUTUP
            Action::make('saveAndClose')
                ->label('Simpan & Tutup')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action(function () {
                    $this->save(); 
                    return redirect($this->getResource()::getUrl('index'));
                }),

            // 3. TOMBOL BATAL
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}