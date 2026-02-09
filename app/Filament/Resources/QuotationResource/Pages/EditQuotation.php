<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Hapus (Hanya muncul jika status Draft)
            Actions\DeleteAction::make()
                ->hidden(fn ($record) => $record->status !== 'Draft'),
            
            // Tombol Cetak PDF
            Actions\Action::make('print')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn ($record) => route('print.quotation', $record))
                ->openUrlInNewTab(),
        ];
    }

    // --- KUSTOMISASI TOMBOL BAWAH ---
    protected function getFormActions(): array
    {
        return [
            // 1. TOMBOL SIMPAN
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle')
                ->submit('save')
                // Hilang jika status bukan Draft (karena form readonly)
                ->hidden(fn () => $this->getRecord()->status !== 'Draft'),

            // 2. TOMBOL SIMPAN & KELUAR
            Actions\Action::make('saveAndClose')
                ->label('Simpan & Keluar')
                ->icon('heroicon-m-arrow-left-start-on-rectangle')
                ->color('gray')
                ->action(function () {
                    $this->save(); // Simpan data
                    return redirect($this->getResource()::getUrl('index')); // Redirect ke index
                })
                // Hilang jika status bukan Draft
                ->hidden(fn () => $this->getRecord()->status !== 'Draft'),

            // 3. TOMBOL KEMBALI (Modifikasi tombol Cancel)
            $this->getCancelFormAction()
                ->label('Kembali')
                ->color('gray'),
        ];
    }
}