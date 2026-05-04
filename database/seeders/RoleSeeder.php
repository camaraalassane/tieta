<?php
// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Vider le cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Rôle SUPERADMIN (toutes les permissions) ---
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $allPermissions = Permission::all();
        $superadmin->syncPermissions($allPermissions);
        $this->command->info("✅ Superadmin : " . $superadmin->permissions->count() . " permissions assignées");

        // --- 2. Rôle ADMIN (gestion avancée mais limitée) ---
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = [
            // Utilisateurs (lecture seulement)
            'read user',

            // Rôles (lecture seulement)
            'read role',

            // Permissions (lecture seulement)
            'read permission',

            // Concours (gestion complète)
            'read concours',
            'create concours',
            'update concours',
            'delete concours',
            'creer concours',
            'modifier concours',
            'supprimer concours',
            'voir concours',

            // Services (gestion partielle)
            'gerer service',
            'gerer admins service',
            'voir service',

            // Résultats
            'gerer resultats',
            'publier resultats',
            'exporter resultats',
            'voir resultats',

            // Messagerie
            'envoyer messages',
            'lire messages',

            // Candidatures
            'voir candidatures',
            'gerer candidatures',
            'exporter candidatures',

            // Dashboard et stats
            'voir dashboard',
            'voir statistiques',
            'exporter statistiques',
        ];

        $admin->syncPermissions($adminPermissions);
        $this->command->info("✅ Admin : " . $admin->permissions->count() . " permissions assignées");

        // --- 3. Rôle GERANT (gestion complète de son service) ---
        $gerant = Role::firstOrCreate(['name' => 'gerant', 'guard_name' => 'web']);
        $gerantPermissions = [
            // Utilisateurs (son personnel seulement)
            'read user',

            // Rôles (lecture limitée)
            'read role',

            // Permissions (lecture limitée)
            'read permission',

            // Concours (gestion complète de son service)
            'read concours',
            'create concours',
            'update concours',
            'delete concours',
            'creer concours',
            'modifier concours',
            'supprimer concours',
            'voir concours',

            // Services (gestion de son service)
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

            // Candidatures
            'voir candidatures',
            'gerer candidatures',
            'exporter candidatures',

            // Dashboard et stats
            'voir dashboard',
            'voir statistiques',
            'exporter statistiques',
        ];

        $gerant->syncPermissions($gerantPermissions);
        $this->command->info("✅ Gérant : " . $gerant->permissions->count() . " permissions assignées");

        // --- 4. Rôle OPERATOR (candidat) ---
        $operator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $operatorPermissions = [
            // Candidatures
            'postuler',
            'voir candidatures',

            // Messagerie
            'lire messages',
            'envoyer messages',

            // Dashboard
            'voir dashboard',

            // Résultats (consultation seulement)
            'voir resultats',

            // Concours (consultation seulement)
            'voir concours',
        ];

        $operator->syncPermissions($operatorPermissions);
        $this->command->info("✅ Operator : " . $operator->permissions->count() . " permissions assignées");

        // --- 5. Vérifications supplémentaires ---
        $this->command->info("\n📊 Récapitulatif des rôles :");
        $this->command->info("- Superadmin : " . $superadmin->name);
        $this->command->info("- Admin : " . $admin->name);
        $this->command->info("- Gérant : " . $gerant->name);
        $this->command->info("- Operator : " . $operator->name);

        // Vérifier les permissions manquantes pour chaque rôle
        $this->checkMissingPermissions($superadmin, 'superadmin');
        $this->checkMissingPermissions($admin, 'admin');
        $this->checkMissingPermissions($gerant, 'gerant');
        $this->checkMissingPermissions($operator, 'operator');

        // Vider le cache après assignation
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info("\n✅ Seeders exécutés avec succès !");
    }

    /**
     * Vérifie les permissions manquantes pour un rôle
     */
    private function checkMissingPermissions(Role $role, string $roleName): void
    {
        $allPermissions = Permission::all()->pluck('name')->toArray();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $missingPermissions = array_diff($allPermissions, $rolePermissions);

        if (!empty($missingPermissions) && $roleName === 'superadmin') {
            $this->command->warn("⚠️ Attention : Le rôle {$roleName} n'a pas toutes les permissions !");
            $this->command->warn("Permissions manquantes : " . implode(', ', $missingPermissions));
        }
    }
}
