<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkpermitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workpermits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->integer('renewalno');
            $table->string('number');
            $table->date('passedate');
            $table->date('idate');
            $table->date('edate');
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
        Schema::dropIfExists('workpermits');
    }
}
