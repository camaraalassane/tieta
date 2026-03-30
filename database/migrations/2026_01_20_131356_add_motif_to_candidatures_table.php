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
           Schema::table('candidatures', function (Blueprint $column) {
            // Ajout de la colonne motif après la colonne resultat
            $column->text('motif')->nullable()->after('resultat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatures', function (Blueprint $column) {
            $column->dropColumn('motif');
        });
    }
};
