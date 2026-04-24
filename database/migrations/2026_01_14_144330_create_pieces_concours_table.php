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
        Schema::create('pieces_concours', function (Blueprint $table) {
                 $table->id();
            // Lien avec la table concours (on utilise 'concour_id' selon votre modèle précédent)
            $table->foreignId('concour_id')->constrained('concours')->onDelete('cascade');
            
            $table->string('nom_document'); // ex: "Attestation de service", "Diplôme de Master"
            $table->string('slug'); // ex: "attestation_service" (utile pour le nom du champ dans le formulaire)
            $table->boolean('is_required')->default(true); // Permet de rendre une pièce facultative
            $table->string('description')->nullable(); // Instructions pour le candidat
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieces_concours');
    }
};
