<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('backup.view');
    }

    public function create(User $user): bool
    {
        return $user->can('backup.create');
    }

    public function delete(User $user): bool
    {
        return $user->can('backup.delete');
    }
    
    // Download biasanya dihandle plugin via viewAny, tapi logic permissionnya aman.
}