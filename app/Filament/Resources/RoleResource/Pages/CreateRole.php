<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        $record = parent::handleRecordCreation($data);

        if (method_exists($record, 'permissions')) {
            $record->permissions()->sync($permissionIds);
        }

        return $record;
    }
}
