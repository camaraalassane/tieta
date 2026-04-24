<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ⭐ 1. Récupérer ou créer le service DTTIA
        $service = Service::firstOrCreate(
            ['nom' => 'DTTIA'],
            [
                'slug' => Str::slug('DTTIA'),
                'description' => 'D
Direction des Transmissions, des Télécommunications et l\'Informatique des Armées s',
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Service DTTIA (ID: {$service->id})");

        // ⭐ 2. Supprimer les anciennes associations pour éviter les doublons
        DB::table('service_users')->where('service_id', $service->id)->delete();

        // ⭐ 3. Superadmin
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@superadmin.com'],
            [
                'name' => 'Superadmin',
                'prenom' => 'System',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $superadmin->syncRoles(['superadmin']);
        $this->command->info("✅ Superadmin: superadmin@superadmin.com");

        // ⭐ 4. Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'prenom' => 'System',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        DB::table('service_users')->insert([
            'service_id' => $service->id,
            'user_id' => $admin->id,
            'role_in_service' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("✅ Admin: admin@admin.com (associé au service)");

        // ⭐ 5. Gérant - CRITIQUE
        $gerant = User::updateOrCreate(
            ['email' => 'gerant@gerant.com'],
            [
                'name' => 'Gerant',
                'prenom' => 'Test',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $gerant->syncRoles(['gerant']);

        // ⭐ CRITIQUE: Associer le gérant au service
        DB::table('service_users')->insert([
            'service_id' => $service->id,
            'user_id' => $gerant->id,
            'role_in_service' => 'gerant',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mettre à jour la colonne gerant_id dans la table services
        $service->update(['gerant_id' => $gerant->id]);

        $this->command->info("✅ Gérant: gerant@gerant.com (associé au service comme GÉRANT)");

        // ⭐ 6. Opérateurs
        $operator = User::updateOrCreate(
            ['email' => 'operator@operator.com'],
            [
                'name' => 'Operator',
                'prenom' => 'Test',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $operator->syncRoles(['operator']);
        $this->command->info("✅ Operator: operator@operator.com");

        $operator2 = User::updateOrCreate(
            ['email' => 'candidat@candidat.com'],
            [
                'name' => 'Candidat',
                'prenom' => 'Jean',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $operator2->syncRoles(['operator']);
        $this->command->info("✅ Second operator: candidat@candidat.com");

        // ⭐ 7. Vérification finale
        $this->command->info("\n📊 Vérification des associations :");
        $associations = DB::table('service_users')
            ->where('service_id', $service->id)
            ->get();

        foreach ($associations as $assoc) {
            $user = User::find($assoc->user_id);
            $this->command->info("- {$user->email} : rôle = {$assoc->role_in_service}");
        }

        $this->command->info("\n🔐 Identifiants de connexion :");
        $this->command->info("Superadmin : superadmin@superadmin.com / 12345678");
        $this->command->info("Admin : admin@admin.com / 12345678");
        $this->command->info("Gérant : gerant@gerant.com / 12345678");
        $this->command->info("Operator : operator@operator.com / 12345678");
        $this->command->info("Candidat : candidat@candidat.com / 12345678");
    }
}
