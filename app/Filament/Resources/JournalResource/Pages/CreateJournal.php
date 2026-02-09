<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;
        $lines = collect($data['lines']);
        
        $debit = $lines->where('direction', 'debit')->sum('amount');
        $credit = $lines->where('direction', 'credit')->sum('amount');

        // Gunakan floating point comparison yang aman (epsilon)
        if (abs($debit - $credit) > 0.01) {
            Notification::make()
                ->title('Jurnal Tidak Balance!')
                ->body("Total Debit: " . number_format($debit) . " | Total Kredit: " . number_format($credit))
                ->danger()
                ->send();
            
            $this->halt(); // Hentikan proses penyimpanan
        }
    }
}