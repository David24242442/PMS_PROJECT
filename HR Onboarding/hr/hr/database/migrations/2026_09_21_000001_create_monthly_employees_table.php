<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('Monthly_Employees')) {
            Schema::create('Monthly_Employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sr_no')->nullable();
                $table->string('emp_id', 50)->index();
                $table->string('employee_name', 190)->index();
                $table->string('location', 190)->nullable()->index();
                $table->string('designation', 190)->nullable();
                $table->string('sex', 20)->nullable();
                $table->string('category', 100)->nullable()->index(); // CONTRACT, PERMANENT, OUTSOURCE
                $table->string('month_year', 50)->default('AUGUST 2026')->index();
                $table->string('status', 50)->default('ACTIVE')->index();
                $table->timestamps();

                $table->index(['emp_id', 'month_year']);
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
        Schema::dropIfExists('Monthly_Employees');
    }
}
