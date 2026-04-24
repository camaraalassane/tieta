<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('profils', function (Blueprint $table) {
            // Ajoute la colonne user_id
            // constrained() devine automatiquement la table 'users'
            // onDelete('cascade') supprime le profil si l'utilisateur est supprimé
            $table->foreignId('user_id')
                  ->after('id') // Place la colonne après l'ID
                  ->constrained()
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('profils', function (Blueprint $table) {
            // Supprime la clé étrangère et la colonne
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
