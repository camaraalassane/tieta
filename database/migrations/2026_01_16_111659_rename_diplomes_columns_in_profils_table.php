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
            $table->renameColumn('def', 'DEF');
            $table->renameColumn('bac', 'BAC');
            $table->renameColumn('cap', 'CAP');
            $table->renameColumn('bt', 'BT');
            $table->renameColumn('dut', 'DUT');
            $table->renameColumn('licence', 'Licence');
            $table->renameColumn('master', 'Master');
            $table->renameColumn('doctorat', 'Doctorat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('profils', function (Blueprint $table) {
            $table->renameColumn('DEF', 'def');
            $table->renameColumn('BAC', 'bac');
            $table->renameColumn('CAP', 'cap');
            $table->renameColumn('BT', 'bt');
            $table->renameColumn('DUT', 'dut');
            $table->renameColumn('Licence', 'licence');
            $table->renameColumn('Master', 'master');
            $table->renameColumn('Doctorat', 'doctorat');
        });
    }
};
