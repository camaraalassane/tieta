<?php
// database/migrations/xxxx_xx_xx_create_evenements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('user_name');
            $table->string('user_email');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->string('service_nom')->nullable();
            $table->string('type_action');
            $table->text('description');
            $table->string('entite')->nullable();
            $table->unsignedBigInteger('entite_id')->nullable();
            $table->json('donnees_avant')->nullable();
            $table->json('donnees_apres')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('service_id');
            $table->index('type_action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
