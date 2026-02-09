<?php

namespace App\Filament\Resources\BirthdayReminderResource\Pages;

use App\Filament\Resources\BirthdayReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBirthdayReminders extends ListRecords
{
    protected static string $resource = BirthdayReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
