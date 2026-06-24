<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wives', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->string('name');
            $table->string('occupation');
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
        Schema::dropIfExists('wives');
    }
}
