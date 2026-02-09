<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Traits\HasEditFormActions;

class EditUser extends EditRecord
{
    use HasEditFormActions;
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Cukup sisakan ini agar kembali ke list setelah save
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}