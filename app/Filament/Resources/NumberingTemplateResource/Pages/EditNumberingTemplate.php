<?php

namespace App\Filament\Resources\NumberingTemplateResource\Pages;

use App\Filament\Resources\NumberingTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNumberingTemplate extends EditRecord
{
    protected static string $resource = NumberingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
