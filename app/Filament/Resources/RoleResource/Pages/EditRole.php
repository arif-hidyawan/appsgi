<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use App\Traits\HasEditFormActions;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    use HasEditFormActions;

    // Hapus mutateFormDataBeforeFill
    // Hapus handleRecordUpdate
    
    // Filament akan otomatis menyimpan permission karena Anda sudah
    // menggunakan ->relationship('permissions') di RoleResource.php
    
}