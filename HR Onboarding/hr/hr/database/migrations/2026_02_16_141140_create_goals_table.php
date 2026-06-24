<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->json('description')->nullable();
            $table->json('purposes')->nullable();
            $table->json('challenges')->nullable();
            $table->string('category')->default('General');
            $table->integer('target')->default(100);
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->integer('year');
            $table->unsignedBigInteger('user_id'); 
            $table->string('manager_name')->nullable();
            $table->json('smart_criteria')->nullable();
            $table->json('quarterly_tracking')->nullable();
            $table->json('appraisal_data')->nullable();
            $table->integer('actual')->default(0)->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goals');
    }
}
