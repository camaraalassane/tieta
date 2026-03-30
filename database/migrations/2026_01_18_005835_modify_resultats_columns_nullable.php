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
        Schema::table('resultats', function (Blueprint $table) {
            // Rend les colonnes existantes nullables
            $table->string('fichier')->nullable()->change();
            $table->integer('nombre_candidat')->nullable()->change();
            $table->unsignedBigInteger('concour_id')->change();
            $table->foreign('concour_id')
            ->references('id')
            ->on('concours')
            ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resultats', function (Blueprint $table) {
            // Revenir à l'état initial (non nullable) si nécessaire
            $table->string('fichier')->nullable(false)->change();
            $table->integer('nombre_candidat')->nullable(false)->change();
           
             // Supprimer la clé étrangère
             $table->dropForeign(['concour_id']);
        });
    }
};
