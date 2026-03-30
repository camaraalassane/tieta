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
            // 2. Changer le type de concour_id en string
            $table->bigInteger('concour_id')->change(); });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('resultat', function (Blueprint $table) {
 // Remettre l'ancien type supposé (ex: integer)
            $table->bigInteger('concour_id')->change();
        });
    }
};
