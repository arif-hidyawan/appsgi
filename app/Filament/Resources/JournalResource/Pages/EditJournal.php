<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
{
    // Copy logika validasi yang sama persis seperti di atas
    $data = $this->data;
    $lines = collect($data['lines']);
    
    $debit = $lines->where('direction', 'debit')->sum('amount');
    $credit = $lines->where('direction', 'credit')->sum('amount');

    if (abs($debit - $credit) > 0.01) {
        Notification::make()
            ->title('Jurnal Tidak Balance!')
            ->body("Total Debit: " . number_format($debit) . " | Total Kredit: " . number_format($credit))
            ->danger()
            ->send();
        
        $this->halt();
    }
}
}
