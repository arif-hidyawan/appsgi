<?php

namespace App\Filament\Resources\PurchasePaymentResource\Pages;

use App\Filament\Resources\PurchasePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchasePayment extends EditRecord
{
    protected static string $resource = PurchasePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
