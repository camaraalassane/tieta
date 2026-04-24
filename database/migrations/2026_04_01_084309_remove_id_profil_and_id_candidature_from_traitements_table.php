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
        Schema::table('traitements', function (Blueprint $table) {
            // Supprimer les colonnes
            $table->dropColumn(['id_profil', 'id_candidature']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traitements', function (Blueprint $table) {
            // Recréer les colonnes en cas de rollback
            $table->unsignedBigInteger('id_profil')->nullable()->after('id_concours');
            $table->unsignedBigInteger('id_candidature')->nullable()->after('id_profil');

            // Ajouter les clés étrangères si nécessaire
            // $table->foreign('id_profil')->references('id')->on('profils')->onDelete('cascade');
            // $table->foreign('id_candidature')->references('id')->on('candidatures')->onDelete('cascade');
        });
    }
};
