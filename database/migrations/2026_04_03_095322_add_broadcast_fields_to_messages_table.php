<?php
// database/migrations/xxxx_add_broadcast_fields_to_messages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_broadcast')->default(false)->after('lu');
            $table->string('broadcast_subject')->nullable()->after('is_broadcast');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_broadcast', 'broadcast_subject']);
        });
    }
};
