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
        Schema::table('profils', function (Blueprint $table) {
            // Supprime la colonne qui cause l'erreur
           $table->string('nom')->nullable()->change();
           $table->string('prenom')->nullable()->change();
           $table->string('email')->nullable()->change();
           $table->integer('telephone')->nullable()->change();
           $table->string('region')->nullable()->change();
           $table->string('carte_identite')->nullable()->change();
           $table->string('photo_identite')->nullable()->change();
           $table->string('def')->nullable()->change();
           $table->string('bac')->nullable()->change();
           $table->string('dut')->nullable()->change();
           $table->string('licence')->nullable()->change();
           $table->string('master')->nullable()->change();
           $table->string('doctorat')->nullable()->change();
           $table->string('permis')->nullable()->change();
           $table->string('bt')->nullable();
           $table->string('cap')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profils', function (Blueprint $table) {
            // En cas de rollback, on recrée la colonne
           // $table->string('nom')->nullable();
        });
    }
};
