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
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profils', function (Blueprint $table) {
            //
             $table->integer('telephone')->after("email");
              $table->string('carte_identite');
               $table->string('photo_identite');
                $table->string('def');
                 $table->string('bac');
                  $table->string('dut');
                   $table->string('licence');
                    $table->string('master');
                     $table->string('doctorat');
                      $table->string('permis');

        });
    }
};
