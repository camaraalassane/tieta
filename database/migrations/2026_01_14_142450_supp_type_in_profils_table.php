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
                 $table->dropColumn([
                'nationalite', 
                'casier_judiciaire', 
                'certificat_nonsup_bac', 
                'certificat_celibatsans_enf'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profils', function (Blueprint $table) {
          $table->string('nationalite')->nullable();
            $table->string('casier_judiciaire')->nullable();
            $table->string('certificat_nonsup_bac')->nullable();
            $table->string('certificat_celibatsans_enf')->nullable();
        });
    }
};
