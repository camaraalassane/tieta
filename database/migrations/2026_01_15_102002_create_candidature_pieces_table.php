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
       Schema::create('candidature_pieces', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidature_id')->constrained()->onDelete('cascade');
    $table->foreignId('piece_concour_id')->constrained('pieces_concours')->onDelete('cascade');
    $table->string('nom_fichier'); // Nom original (ex: mon_diplome.pdf)
    $table->string('url_fichier'); // Chemin dans le storage
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidature_pieces');
    }
};
