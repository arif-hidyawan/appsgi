<?php

namespace App\Filament\Resources\ReceiveGoodsResource\Pages;

use App\Filament\Resources\ReceiveGoodsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceiveGoods extends EditRecord
{
    protected static string $resource = ReceiveGoodsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
