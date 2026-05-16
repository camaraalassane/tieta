<?php
// database/migrations/2024_01_01_000001_add_fichier_to_traitements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFichierToTraitementsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('traitements', function (Blueprint $table) {
            // ⭐ Ajouter la colonne fichier pour stocker le chemin du fichier joint
            $table->string('fichier')->nullable()->after('communique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traitements', function (Blueprint $table) {
            $table->dropColumn('fichier');
        });
    }
}
