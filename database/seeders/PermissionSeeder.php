<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Vider le cache des permissions avant de commencer
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Liste complète des permissions
        $permissions = [
            // Utilisateurs
            'delete user',
            'update user',
            'read user',
            'create user',

            // Rôles
            'delete role',
            'update role',
            'read role',
            'create role',

            // Permissions
            'delete permission',
            'update permission',
            'read permission',
            'create permission',

            // Concours (français)
            'read concours',
            'create concours',
            'update concours',
            'delete concours',

            // Concours (synonymes français)
            'creer concours',
            'modifier concours',
            'supprimer concours',
            'voir concours',

            // Services
            'gerer service',
            'gerer admins service',
            'gerer personnel service',
            'voir service',

            // Résultats
            'gerer resultats',
            'publier resultats',
            'exporter resultats',
            'voir resultats',

            // Messagerie
            'envoyer messages',
            'lire messages',
            'supprimer messages',

            // Candidatures
            'postuler',
            'voir candidatures',
            'gerer candidatures',
            'exporter candidatures',

            // Dashboard
            'voir dashboard',

            // Statistiques
            'voir statistiques',
            'exporter statistiques',
        ];

        $createdCount = 0;
        $existingCount = 0;

        foreach ($permissions as $permission) {
            try {
                $perm = Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);

                if ($perm->wasRecentlyCreated) {
                    $createdCount++;
                    $this->command->info("Permission créée : {$permission}");
                } else {
                    $existingCount++;
                }
            } catch (\Exception $e) {
                $this->command->error("Erreur lors de la création de la permission {$permission} : " . $e->getMessage());
                Log::error("Erreur PermissionSeeder: " . $e->getMessage());
            }
        }

        // Vider le cache après création
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info("✅ {$createdCount} permissions créées, {$existingCount} permissions existantes.");
        $this->command->info("Total des permissions : " . Permission::count());
    }
}
