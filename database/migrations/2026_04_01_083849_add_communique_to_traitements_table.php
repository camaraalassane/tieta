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
            // Ajouter la colonne communique (texte long)
            $table->text('communique')->nullable()->after('id_candidature');

            // Ajouter un champ pour le titre du communiqué (optionnel)
            $table->string('communique_titre')->nullable()->after('communique');

            // Ajouter un champ pour la date de publication
            $table->timestamp('communique_published_at')->nullable()->after('communique_titre');

            // Ajouter un champ pour savoir si le communiqué est actif
            $table->boolean('communique_is_active')->default(false)->after('communique_published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traitements', function (Blueprint $table) {
            $table->dropColumn([
                'communique',
                'communique_titre',
                'communique_published_at',
                'communique_is_active'
            ]);
        });
    }
};
