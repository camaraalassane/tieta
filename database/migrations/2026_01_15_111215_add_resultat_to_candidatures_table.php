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
       Schema::table('candidatures', function (Blueprint $table) {
            // Ajoute la colonne 'resultat'. On la met en 'nullable' pour ne pas bloquer 
            // les anciennes lignes, et 'after' pour l'ordre visuel dans la DB.
            $table->string('resultat')->nullable()->after('concour_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('candidatures', function (Blueprint $table) {
            $table->dropColumn('resultat');
        });
    }
};
