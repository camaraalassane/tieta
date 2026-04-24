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
           Schema::table('users', function (Blueprint $table) {
            // Ajoute la colonne prenom après la colonne name
            $table->string('prenom')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('prenom');
        });
    }
};
