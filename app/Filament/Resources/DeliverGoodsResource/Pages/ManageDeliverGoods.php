<?php

namespace App\Filament\Resources\DeliverGoodsResource\Pages;

use App\Filament\Resources\DeliverGoodsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliverGoods extends ManageRecords
{
    // WAJIB: Definisikan property resource agar sistem mengenali halaman ini milik siapa
    protected static string $resource = DeliverGoodsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Dikosongkan karena gudang hanya mengeksekusi SO, bukan membuat SO baru
        ];
    }
}