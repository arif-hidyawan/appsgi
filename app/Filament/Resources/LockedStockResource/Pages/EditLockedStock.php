<?php

namespace App\Filament\Resources\LockedStockResource\Pages;

use App\Filament\Resources\LockedStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLockedStock extends EditRecord
{
    protected static string $resource = LockedStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
