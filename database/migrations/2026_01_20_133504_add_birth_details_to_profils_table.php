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
            // Ajout des colonnes après le prénom ou le nom
            $table->date('date_naissance')->nullable()->after('prenom');
            $table->string('lieu_naissance')->nullable()->after('date_naissance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('profils', function (Blueprint $table) {
            $table->dropColumn(['date_naissance', 'lieu_naissance']);
        });
    }
};
