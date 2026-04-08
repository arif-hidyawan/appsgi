<?php

namespace App\Filament\Resources\NumberingTemplateResource\Pages;

use App\Filament\Resources\NumberingTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNumberingTemplates extends ListRecords
{
    protected static string $resource = NumberingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
