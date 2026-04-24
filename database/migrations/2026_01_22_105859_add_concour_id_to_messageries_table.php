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
        Schema::table('messages', function (Blueprint $table) {

           $table->foreignId('concour_id')
                  ->constrained('concours')
                  ->onDelete('cascade'); // Optionnel : supprime les messages si le concours est supprimé
        
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
             // Supprime d'abord la clé étrangère puis la colonne
            $table->dropForeign(['concour_id']);
            $table->dropColumn('concour_id');
        });
    }
};
