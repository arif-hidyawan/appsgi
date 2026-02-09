<?php

namespace App\Filament\Resources\PurchasePaymentResource\Pages;

use App\Filament\Resources\PurchasePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchasePayments extends ListRecords
{
    protected static string $resource = PurchasePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
