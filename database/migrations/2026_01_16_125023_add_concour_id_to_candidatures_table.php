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
            // On s'assure que la colonne existante est bien de type UNSIGNED 
            // car les clés étrangères doivent avoir le même type que l'ID référencé
            $table->unsignedBigInteger('concour_id')->change();

            // Ajout de la contrainte
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
        Schema::table('candidatures', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['concour_id']);
        });
    }
    
};
