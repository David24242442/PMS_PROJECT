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
        if (!Schema::hasTable('goals')) {
            Schema::create('goals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->decimal('weight', 5, 2)->default(0);
                $table->integer('target')->default(100);
                $table->integer('actual')->default(0);
                $table->integer('rating')->nullable();
                $table->string('status')->default('not_started'); // not_started, in_progress, completed, on_hold
                $table->date('due_date')->nullable();
                $table->integer('year');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
     
                // Foreign keys
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
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
        Schema::dropIfExists('goals');
    }
}
