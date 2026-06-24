<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksocialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_socials', function (Blueprint $table) {
            $table->id();
            $table->string('bankname');
            $table->string('bankbranch');
            $table->string('accountname');
            $table->bigInteger('accounttype');
            $table->string('accountnumber');
            $table->string('socialfundnumber');
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->bigInteger('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('banksocial');
    }
}
