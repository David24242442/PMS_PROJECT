<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingCsvColumnsToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'full_name')) {
                $table->string('full_name')->nullable();
            }
            if (!Schema::hasColumn('employees', 'job_title')) {
                $table->string('job_title')->nullable();
            }
            if (!Schema::hasColumn('employees', 'department')) {
                $table->string('department')->nullable();
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
            $table->dropColumn(['full_name', 'job_title', 'department']);
        });
    }
}
