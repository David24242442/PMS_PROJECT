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
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->integer('year');
                $table->string('status')->default('draft');
                $table->text('goal_achievement')->nullable();
                $table->text('initiatives')->nullable();
                $table->text('next_steps')->nullable();
                $table->text('improvements')->nullable();
                $table->text('manager_feedback')->nullable();
                $table->date('date')->nullable();
                $table->string('emp_code')->nullable();
                $table->string('name')->nullable();
                $table->string('division')->nullable();
                $table->string('position')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
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
