<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIrrGuaranteesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('irr_guarantees', function (Blueprint $table) {
            $table->id();
            $table->string('guarname');
            $table->string('ssfno');
            $table->decimal('annualincome',20,0);
            $table->decimal('propertyvalue',20,0);
            $table->string('occupation');
            $table->string('mobileno');
            $table->string('businessaddr');
            $table->string('residenceaddr');
            $table->string('ghcard');
            $table->string('digitaladdr');
            $table->bigInteger('relation');
            $table->integer('relationyear');
            $table->string('company');
            $table->string('empposition');
            $table->decimal('guaramount',20,0);
            $table->string('hno');
            $table->string('hnolocation');
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
        Schema::dropIfExists('irr_guarantees');
    }
}
