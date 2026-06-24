<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPmsFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'line_manager_id')) {
                $table->unsignedBigInteger('line_manager_id')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->after('line_manager_id');
            }
            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable()->after('employee_code');
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
            $table->dropColumn(['line_manager_id', 'employee_code', 'location']);
        });
    }
}
