<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste exhaustive de toutes vos permissions
        $permissions = [
            // Users
            'delete user', 'update user', 'read user', 'create user',
            // Roles
            'delete role', 'update role', 'read role', 'create role',
            // Permissions
            'delete permission', 'update permission', 'read permission', 'create permission',
            // Concours
            'read concours', 'create concours', 'update concours', 'delete concours',
        ];

        foreach ($permissions as $permission) {
            // Utilisation systématique de firstOrCreate pour éviter les doublons
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Nettoyage automatique du cache Spatie après le seeding
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
