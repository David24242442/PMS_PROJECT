<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmergencyContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->string('fullname');
            $table->string('workaddress')->nullable();
            $table->string('ghcardno');
            $table->string('ghcardfile')->nullable();
            $table->string('mobileno');
            $table->string('altnumber')->nullable();
            $table->bigInteger('relation');
            $table->boolean('isnextofkin')->default(false);
            $table->boolean('livestatus')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emergency_contacts');
    }
}
