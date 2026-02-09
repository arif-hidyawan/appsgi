<?php

namespace App\Filament\Resources\StockClassificationResource\Pages;

use App\Filament\Resources\StockClassificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockClassification extends EditRecord
{
    protected static string $resource = StockClassificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
