<?php
// database/migrations/xxxx_xx_xx_add_has_specialites_to_concours_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->boolean('has_specialites')->default(false)->after('statut');
        });
    }

    public function down()
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->dropColumn('has_specialites');
        });
    }
};
