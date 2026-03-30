<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HugeDataSeeder extends Seeder
{
    public function run(): void
    {
        // On ne fait PAS de truncate ici.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $roleSuperAdmin = DB::table('roles')->where('name', 'superadmin')->value('id');
        $roleAdmin = DB::table('roles')->where('name', 'admin')->value('id');
        $roleOperator = DB::table('roles')->where('name', 'operator')->value('id');
        
        $concours = DB::table('concours')->get();
        $password = Hash::make('password');

        $this->command->info('--- Vérification et Création du personnel (35) ---');
        
        for ($i = 1; $i <= 35; $i++) {
            $email = "staff$i@test.com";
            
            // Vérification si l'email existe déjà
            $userExists = DB::table('users')->where('email', $email)->first();

            if (!$userExists) {
                $isSuper = ($i <= 5);
                $uId = DB::table('users')->insertGetId([
                    'name' => $isSuper ? "SuperAdmin_$i" : "Admin_$i",
                    'email' => $email,
                    'password' => $password,
                    'created_at' => now(),
                ]);

                DB::table('profils')->insert([
                    'user_id' => $uId,
                    'nom' => $isSuper ? "SUPER" : "ADMIN",
                    'prenom' => "Staff$i",
                    'email' => $email,
                ]);

                DB::table('model_has_roles')->insert([
                    'role_id' => $isSuper ? $roleSuperAdmin : $roleAdmin,
                    'model_type' => 'App\Models\User',
                    'model_id' => $uId
                ]);

                DB::table('concour_admins')->insert([
                    'concour_id' => $concours->random()->id,
                    'user_id' => $uId,
                    'role' => 'admin' 
                ]);
            }
        }

        $this->command->info('--- Création sécurisée des 50 000 opérateurs ---');

        for ($b = 0; $b < 50; $b++) {
            DB::transaction(function () use ($concours, $password, $roleOperator) {
                for ($i = 0; $i < 1000; $i++) {
                    // Utilisation de microtime pour garantir l'unicité absolue de l'email
                    $token = Str::random(4) . bin2hex(random_bytes(3));
                    $email = "op_" . time() . "_$token@test.com";
                    
                    $uId = DB::table('users')->insertGetId([
                        'name' => "User_$token",
                        'email' => $email,
                        'password' => $password,
                        'created_at' => now(),
                    ]);

                    $pId = DB::table('profils')->insertGetId([
                        'user_id' => $uId,
                        'nom' => "Nom_$token",
                        'prenom' => "Opérateur",
                        'email' => $email,
                    ]);

                    DB::table('model_has_roles')->insert([
                        'role_id' => $roleOperator,
                        'model_type' => 'App\Models\User',
                        'model_id' => $uId
                    ]);

                    $cId = DB::table('candidatures')->insertGetId([
                        'num_dossier' => 'D-' . strtoupper($token . rand(10, 99)),
                        'demande_lettre' => 'lettre_' . $token . '.pdf',
                        'profil_id' => $pId,
                        'concour_id' => $concours->random()->id,
                        'nationalite' => 'Malienne',
                        'resultat' => 'pending',
                        'created_at' => now(),
                    ]);

                    DB::table('candidature_pieces')->insert([
                        'candidature_id' => $cId,
                        'piece_concour_id' => 1,
                        'nom_fichier' => 'Doc_Test.pdf',
                        'url_fichier' => 'uploads/test.pdf'
                    ]);
                }
            });
            $this->command->comment("Batch " . ($b + 1) . "/50 terminé sans collision.");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}