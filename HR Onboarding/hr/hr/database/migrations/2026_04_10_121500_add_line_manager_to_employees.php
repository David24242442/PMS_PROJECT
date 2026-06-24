<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLineManagerToEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Check if column exists before adding it
            if (!Schema::hasColumn('employees', 'line_manager_id')) {
                $table->unsignedBigInteger('line_manager_id')->nullable()->after('employeeid');
            }
            if (!Schema::hasColumn('employees', 'full_name')) {
                $table->string('full_name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('employees', 'department')) {
                $table->string('department')->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('employees', 'job_title')) {
                $table->string('job_title')->nullable()->after('department');
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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['line_manager_id', 'full_name', 'department', 'job_title']);
        });
    }
}
