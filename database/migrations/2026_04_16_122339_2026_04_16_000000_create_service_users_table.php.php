<?php
// database/migrations/2026_04_16_000000_create_service_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role_in_service', ['gerant', 'admin'])->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Un utilisateur ne peut être qu'une seule fois dans un service
            $table->unique(['service_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_users');
    }
};
