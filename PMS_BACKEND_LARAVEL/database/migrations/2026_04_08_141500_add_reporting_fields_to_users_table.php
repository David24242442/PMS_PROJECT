<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportingFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('employee_code');
            }
            if (!Schema::hasColumn('users', 'above_line_manager_id')) {
                $table->unsignedBigInteger('above_line_manager_id')->nullable()->after('line_manager_id');
                
                // Optional: add foreign key if you want strict integrity
                // $table->foreign('above_line_manager_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['job_title', 'above_line_manager_id']);
        });
    }
}
