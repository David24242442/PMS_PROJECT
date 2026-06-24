<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->string('status')->default('draft'); // draft, submitted
            
            // Employee Info Snapshot
            $table->string('emp_code')->nullable();
            $table->string('name')->nullable();
            $table->string('division')->nullable();
            $table->string('position')->nullable();
            $table->date('date')->nullable();

            // Sections
            $table->json('goal_achievement')->nullable();
            $table->json('initiatives')->nullable();
            $table->json('next_steps')->nullable();
            $table->json('improvements')->nullable();
            $table->json('manager_feedback')->nullable();

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
        Schema::dropIfExists('reviews');
    }
}
