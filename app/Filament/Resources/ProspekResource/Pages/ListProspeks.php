<?php

namespace App\Filament\Resources\ProspekResource\Pages;

use App\Filament\Resources\ProspekResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProspeks extends ListRecords
{
    protected static string $resource = ProspekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
