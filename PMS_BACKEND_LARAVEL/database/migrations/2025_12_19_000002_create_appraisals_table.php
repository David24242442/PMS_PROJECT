<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppraisalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
            $table->string('status')->default('draft'); // draft, pending, reviewed, approved, rejected
            $table->decimal('overall_rating', 5, 2)->nullable();
            $table->json('goals_data')->nullable(); // Snapshot of goals and their ratings
            $table->json('competencies_data')->nullable(); // Ratings for various performance criteria
            $table->text('self_assessment')->nullable();
            $table->text('achievements')->nullable();
            $table->text('improvements')->nullable();
            $table->text('development_plan')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('hr_comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appraisals');
    }
}
