<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cache permission agar tidak error
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Definisi Struktur Role & Permission
        $rolesStructure = [
            'Administrator' => [
                'target_user_id' => 4,
                'permissions' => [
                    'admin.users.manage',
                    'admin.roles.manage',
                    'admin.settings',
                ],
            ],
            'Sales Staff' => [
                'target_user_id' => 5,
                'permissions' => [
                    'sales.view',
                    'sales.create',
                    'sales.update',
                    'sales.delete',
                ],
            ],
            'Purchasing Staff' => [
                'target_user_id' => 6,
                'permissions' => [
                    'purchasing.view',
                    'purchasing.create',
                    'purchasing.approve',
                    'purchasing.delete',
                ],
            ],
            'Warehouse Staff' => [
                'target_user_id' => 7,
                'permissions' => [
                    'warehouse.view',
                    'warehouse.adjust',
                    'warehouse.transfer',
                ],
            ],
            'Finance Staff' => [
                'target_user_id' => 8,
                'permissions' => [
                    'finance.view',
                    'finance.invoice',
                    'finance.approve',
                ],
            ],
        ];

        DB::transaction(function () use ($rolesStructure) {
            foreach ($rolesStructure as $roleName => $data) {
                // A. Buat Role (guard_name 'web' untuk Filament default)
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

                // B. Pastikan Permission ada di DB, lalu ambil object-nya
                $permissionsToSync = [];
                foreach ($data['permissions'] as $permissionName) {
                    $perm = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                    $permissionsToSync[] = $perm;
                }

                // C. Sync Permission ke Role (role_has_permissions)
                $role->syncPermissions($permissionsToSync);

                // D. Assign Role ke User
                if (isset($data['target_user_id'])) {
                    $user = User::find($data['target_user_id']);
                    
                    if ($user) {
                        // Assign role menggunakan Spatie helper
                        $user->assignRole($role);
                        
                        $this->command->info("Role [{$roleName}] assigned to User ID: {$user->id}");
                    } else {
                        $this->command->warn("User ID {$data['target_user_id']} not found. Skipping assignment.");
                    }
                }
            }
        });

        $this->command->info('Roles and Permissions seeded successfully!');
    }
}