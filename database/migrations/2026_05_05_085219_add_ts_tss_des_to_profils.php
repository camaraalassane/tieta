<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profils', function (Blueprint $table) {
            $table->string('TS')->nullable()->after('Doctorat');
            $table->string('TSS')->nullable()->after('TS');
            $table->string('DES')->nullable()->after('TSS');
        });
    }

    public function down(): void
    {
        Schema::table('profils', function (Blueprint $table) {
            $table->dropColumn(['TS', 'TSS', 'DES']);
        });
    }
};
