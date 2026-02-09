<?php

namespace App\Filament\Resources\LockedStockResource\Pages;

use App\Filament\Resources\LockedStockResource;
use Filament\Resources\Pages\ListRecords;

class ListLockedStocks extends ListRecords
{
    protected static string $resource = LockedStockResource::class;

    // Tidak ada tombol "Create" karena ini hanya view
    protected function getHeaderActions(): array
    {
        return [];
    }
}