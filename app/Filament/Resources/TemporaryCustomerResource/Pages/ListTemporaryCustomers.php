<?php

namespace App\Filament\Resources\TemporaryCustomerResource\Pages;

use App\Filament\Resources\TemporaryCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemporaryCustomers extends ListRecords
{
    protected static string $resource = TemporaryCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
