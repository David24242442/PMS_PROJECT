<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSessionsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_sessions_logs')) {
            Schema::create('user_sessions_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('employee_code')->nullable()->index();
                $table->string('action', 50)->index(); // LOGIN, LOGOUT, SUBMIT, CREATE, UPDATE, DELETE, APPROVE, REJECT, SYNC
                $table->string('module', 50)->index(); // AUTH, ONBOARDING, PMS_GOALS, PMS_APPRAISAL, PMS_REVIEW, EMPLOYEE_MASTER, USERS
                $table->text('description');
                $table->string('ip_address', 50)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('details')->nullable(); // JSON or extra context
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
        Schema::dropIfExists('user_sessions_logs');
    }
}
