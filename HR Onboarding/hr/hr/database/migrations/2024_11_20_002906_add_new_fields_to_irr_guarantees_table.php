<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToIrrGuaranteesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('irr_guarantees', function (Blueprint $table) {
            $table->bigInteger('region_id');
            $table->string('primary_email')->nullable();
            $table->string('secondary_email')->nullable();
            $table->string('secondary_contact_person_name')->nullable();
            $table->string('secondary_contact_person_number')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('irr_guarantees', function (Blueprint $table) {
            //
        });
    }
}
