<?php

namespace App\Filament\Resources\StockClassificationResource\Pages;

use App\Filament\Resources\StockClassificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockClassifications extends ListRecords
{
    protected static string $resource = StockClassificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
