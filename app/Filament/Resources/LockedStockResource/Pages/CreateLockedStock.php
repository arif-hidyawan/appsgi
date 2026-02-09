<?php

namespace App\Filament\Resources\LockedStockResource\Pages;

use App\Filament\Resources\LockedStockResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLockedStock extends CreateRecord
{
    protected static string $resource = LockedStockResource::class;
}
