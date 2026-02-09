<?php

namespace App\Filament\Resources\SalesPaymentResource\Pages;

use App\Filament\Resources\SalesPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesPayments extends ListRecords
{
    protected static string $resource = SalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
