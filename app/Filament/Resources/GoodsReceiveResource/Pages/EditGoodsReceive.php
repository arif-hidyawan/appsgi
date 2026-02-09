<?php

namespace App\Filament\Resources\GoodsReceiveResource\Pages;

use App\Filament\Resources\GoodsReceiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Traits\HasEditFormActions;

class EditGoodsReceive extends EditRecord
{
    use HasEditFormActions;
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol hapus tetap ada (bisa ditambah ->visible() jika ingin dikunci)
            Actions\DeleteAction::make(),
        ];
    }
}