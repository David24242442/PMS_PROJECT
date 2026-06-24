<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIrrGuarWitnessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('irr_guar_witnesses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('irrguar_id')->references('id')->on('irr_guarantees');
            $table->string('name');
            $table->string('address');
            $table->string('phoneno');
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
        Schema::dropIfExists('irr_guar_witnesses');
    }
}
