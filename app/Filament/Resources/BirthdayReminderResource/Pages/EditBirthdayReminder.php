<?php

namespace App\Filament\Resources\BirthdayReminderResource\Pages;

use App\Filament\Resources\BirthdayReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Traits\HasEditFormActions;

class EditBirthdayReminder extends EditRecord
{
    use HasEditFormActions;
    protected static string $resource = BirthdayReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
