<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_code');
            $table->string('fullname');
            $table->string('ghcardno');
            $table->string('ghcardfile')->nullable();
            $table->string('raddress')->nullable();
            $table->string('daddress')->nullable();
            $table->string('hometown');
            $table->string('hdaddress');
            $table->string('mobileno');
            $table->string('altnumber')->nullable();
            $table->string('email')->unique();
            $table->string('socialsecurityno')->unique();
            $table->date('dob');
            $table->bigInteger('maritalstatus');
            $table->string('fathersname');
            $table->string('mothersname');
            $table->string('spousename')->nullable();
            $table->string('spouseoccupation')->nullable();
            $table->longText('anyotherinfo')->nullable();
            $table->boolean('studentloan')->default(false);
            $table->boolean('status')->default(true);
            $table->longText('signature')->nullable();
            $table->date('signaturedate')->nullable();
            $table->bigInteger('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('employees');
    }
}
