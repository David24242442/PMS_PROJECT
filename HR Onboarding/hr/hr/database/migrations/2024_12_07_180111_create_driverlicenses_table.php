<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverlicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driverlicenses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->string('name');
            $table->string('licenseno');
            $table->date('idate');
            $table->date('edate');
            $table->string('ref');
            $table->date('rdate');
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
        Schema::dropIfExists('driverlicenses');
    }
}
