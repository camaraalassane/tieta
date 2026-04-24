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
             $table->integer('telephone')->after("email");
              $table->string('carte_identite')->change();
               $table->string('photo_identite')->change();
                $table->string('def')->change();
                 $table->string('bac')->change();
                  $table->string('dut')->change();
                   $table->string('licence')->change();
                    $table->string('master')->change();
                     $table->string('doctorat')->change();
                      $table->string('permis')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profils', function (Blueprint $table) {
                    $table->string('carte_identite')->nullable()->change();
            $table->string('telephone')->nullable()->change();
            $table->string('photo_identite')->nullable()->change();
                 $table->string('def')->change();
                 $table->string('bac')->change();
                  $table->string('dut')->change();
                   $table->string('licence')->change();
                    $table->string('master')->change();
                     $table->string('doctorat')->change();
                      $table->string('permis')->change();
        
        });
    }
};
