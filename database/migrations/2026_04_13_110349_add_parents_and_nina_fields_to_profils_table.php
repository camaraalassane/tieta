<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('profils', function (Blueprint $table) {
            $table->string('nina')->nullable()->after('region');
            $table->string('prenom_pere')->nullable()->after('nina');
            $table->string('prenom_mere')->nullable()->after('prenom_pere');
            $table->string('nom_mere')->nullable()->after('prenom_mere');
        });
    }

    public function down()
    {
        Schema::table('profils', function (Blueprint $table) {
            $table->dropColumn(['nina', 'prenom_pere', 'prenom_mere', 'nom_mere']);
        });
    }
};
