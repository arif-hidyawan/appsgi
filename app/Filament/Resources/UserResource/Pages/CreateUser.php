<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        $record = parent::handleRecordCreation($data);

        if (method_exists($record, 'roles')) {
            $record->roles()->sync($roleIds);
        }

        return $record;
    }
}
