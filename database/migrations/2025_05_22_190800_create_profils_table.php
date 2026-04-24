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
        Schema::create('profils', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('region');
            $table->binary('carte_identite');
            $table->binary('photo_identite');
            $table->binary('def');
            $table->binary('bac');
            $table->binary('dut');
            $table->binary('licence');
            $table->binary('master');
            $table->binary('doctorat');
            $table->binary('permis');
            $table->binary('nationalite');
            $table->binary('casier_judiciaire');
            $table->binary('certificat_nonsup_bac');
            $table->binary('certificat_celibatsans_enf');
            //$table->string('demande_lettre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profils');
    }
};
