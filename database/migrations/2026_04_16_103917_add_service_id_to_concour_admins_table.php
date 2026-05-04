<?php
// database/migrations/xxxx_xx_xx_add_service_id_to_concour_admins_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('concour_admins', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('concour_admins', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
