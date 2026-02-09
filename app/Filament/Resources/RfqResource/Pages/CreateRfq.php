<?php

namespace App\Filament\Resources\RfqResource\Pages;

use App\Filament\Resources\RfqResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRfq extends CreateRecord
{
    protected static string $resource = RfqResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['created_by'] = auth()->id();
    return $data;
}
}
