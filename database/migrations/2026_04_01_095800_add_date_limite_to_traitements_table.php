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
            // Ajouter la colonne date_limite
            $table->date('date_limite')->nullable()->after('communique_published_at');

            // Optionnel: Ajouter un index pour faciliter les recherches
            $table->index('date_limite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traitements', function (Blueprint $table) {
            $table->dropColumn('date_limite');
            $table->dropIndex(['date_limite']);
        });
    }
};
