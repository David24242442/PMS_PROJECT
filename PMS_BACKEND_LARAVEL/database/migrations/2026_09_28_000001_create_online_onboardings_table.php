<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnlineOnboardingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('online_onboardings')) {
            Schema::create('online_onboardings', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->unique();
                $table->string('candidate_name');
                $table->string('ghcardno')->nullable()->index();
                $table->string('mobileno')->nullable();
                $table->string('email')->nullable();
                $table->string('position')->nullable();
                $table->string('department')->nullable();
                $table->string('branch')->nullable();
                $table->string('company')->nullable();
                $table->longText('submission_data')->nullable();
                $table->string('status')->default('pending')->index(); // pending, approved, rejected
                $table->text('rejection_reason')->nullable();
                $table->string('created_by')->nullable(); // Name & Employee ID of user who approved it
                $table->unsignedBigInteger('approved_by_user_id')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('synced_employee_id')->nullable()->index();
                $table->string('ip_address')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('online_onboardings');
    }
}
