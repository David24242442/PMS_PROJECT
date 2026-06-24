<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHierarchyFieldsToUsersTable extends Migration
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
                $table->unsignedBigInteger('line_manager_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->after('username');
            }
            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable()->after('department');
            }

            // Foreign key constraint (optional, but good for integrity)
            // $table->foreign('line_manager_id')->references('id')->on('users')->onDelete('set null');
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
