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
        Schema::create('concour_admins', function (Blueprint $table) {
            $table->id();
            // Relation vers l'utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Relation vers le concours
            $table->foreignId('concour_id')->constrained()->onDelete('cascade');
            // Optionnel : un rôle spécifique pour cet admin (ex: éditeur, validateur)
            $table->string('role')->default('admin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concour_admins');
    }
};
