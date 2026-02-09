<?php

namespace App\Filament\Resources\BirthdayReminderResource\Pages;

use App\Filament\Resources\BirthdayReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBirthdayReminder extends CreateRecord
{
    protected static string $resource = BirthdayReminderResource::class;
}
