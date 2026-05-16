<?php
// database/migrations/xxxx_xx_xx_add_specialite_id_to_candidatures_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('candidatures', function (Blueprint $table) {
            $table->foreignId('specialite_id')->nullable()->constrained('concour_specialites')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('candidatures', function (Blueprint $table) {
            $table->dropForeign(['specialite_id']);
            $table->dropColumn('specialite_id');
        });
    }
};
