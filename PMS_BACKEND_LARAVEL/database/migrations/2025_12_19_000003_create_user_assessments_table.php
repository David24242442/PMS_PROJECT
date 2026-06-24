<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAssessmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assessed_by');
            $table->json('checklist_items')->nullable();
            $table->integer('attendance_rating')->nullable();
            $table->integer('task_completion_rating')->nullable();
            $table->integer('quality_rating')->nullable();
            $table->integer('teamwork_rating')->nullable();
            $table->integer('initiative_rating')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('action_items')->nullable();
            $table->text('overall_notes')->nullable();
            $table->timestamp('assessment_date');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assessed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_assessments');
    }
}
