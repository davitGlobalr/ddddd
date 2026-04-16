<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = (string) config('auth.defaults.guard', 'web');

        $permissionNames = [
            'booking.create',
            'booking.updateStatus',
        ];

        $permissionModels = [];
        foreach ($permissionNames as $permissionName) {
            $permissionModels[$permissionName] = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        }

        $roles = ['superadmin', 'manager', 'customer'];
        $roleModels = [];
        foreach ($roles as $roleName) {
            $roleModels[$roleName] = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);
        }

        // Superadmin: all permissions.
        $roleModels['superadmin']->syncPermissions(array_values($permissionModels));

        // Manager: only can update booking status.
        $roleModels['manager']->syncPermissions([
            $permissionModels['booking.updateStatus'],
        ]);

        // Customer: only can create booking.
        $roleModels['customer']->syncPermissions([
            $permissionModels['booking.create'],
        ]);

        // Ensure cached permissions (if enabled) are refreshed right away.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
