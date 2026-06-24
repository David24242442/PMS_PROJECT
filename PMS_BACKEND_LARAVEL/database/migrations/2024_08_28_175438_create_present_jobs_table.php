<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePresentJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('present_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->bigInteger('emp_id')->references('id')->on('employees');
            $table->date('doj');
            $table->bigInteger('dept_id');
            $table->bigInteger('branch_id');
            $table->bigInteger('region_id');
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
        Schema::dropIfExists('present_jobs');
    }
}
