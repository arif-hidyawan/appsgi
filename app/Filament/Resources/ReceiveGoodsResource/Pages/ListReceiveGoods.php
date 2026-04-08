<?php

namespace App\Filament\Resources\ReceiveGoodsResource\Pages;

use App\Filament\Resources\ReceiveGoodsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceiveGoods extends ListRecords
{
    protected static string $resource = ReceiveGoodsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
