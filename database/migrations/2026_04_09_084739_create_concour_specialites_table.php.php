<?php
// database/migrations/xxxx_xx_xx_create_concour_specialites_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('concour_specialites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concour_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('places_disponibles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('concour_specialites');
    }
};
