<?php

namespace App\Filament\Resources\TemporaryCustomerResource\Pages;

use App\Filament\Resources\TemporaryCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTemporaryCustomer extends CreateRecord
{
    protected static string $resource = TemporaryCustomerResource::class;
}
