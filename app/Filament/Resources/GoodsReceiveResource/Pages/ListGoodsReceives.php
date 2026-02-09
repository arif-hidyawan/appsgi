<?php

namespace App\Filament\Resources\GoodsReceiveResource\Pages;

use App\Filament\Resources\GoodsReceiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceives extends ListRecords
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
