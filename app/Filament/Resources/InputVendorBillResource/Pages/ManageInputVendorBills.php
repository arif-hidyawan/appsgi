<?php

namespace App\Filament\Resources\InputVendorBillResource\Pages;

use App\Filament\Resources\InputVendorBillResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInputVendorBills extends ManageRecords
{
    // Wajib didaftarkan agar Filament tahu ini milik resource mana
    protected static string $resource = InputVendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kosong, karena tim Finance tidak membuat PO baru dari sini
        ];
    }
}