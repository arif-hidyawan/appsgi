<?php

namespace App\Filament\Resources\GenerateSalesInvoiceResource\Pages;

use App\Filament\Resources\GenerateSalesInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGenerateSalesInvoices extends ManageRecords
{
    protected static string $resource = GenerateSalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Dikosongkan
    }
}