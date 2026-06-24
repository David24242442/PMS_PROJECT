<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('parameters')->nullable();
            $table->json('data')->nullable(); // Cached report data
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->string('status')->default('generated');
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            // Assuming departments table will be handled or already exists in some form
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hr_reports');
    }
}
