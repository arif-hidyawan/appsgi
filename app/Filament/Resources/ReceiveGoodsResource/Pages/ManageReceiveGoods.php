<?php

namespace App\Filament\Resources\ReceiveGoodsResource\Pages;

use App\Filament\Resources\ReceiveGoodsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageReceiveGoods extends ManageRecords
{
    // Di sinilah properti $resource didefinisikan agar tidak memicu error!
    protected static string $resource = ReceiveGoodsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kosongkan agar tidak ada tombol "New" di pojok kanan atas, 
            // karena Gudang hanya menerima barang, bukan membuat PO baru.
        ];
    }
}