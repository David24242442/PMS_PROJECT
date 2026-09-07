<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            if (!Schema::hasColumn('goals', 'candidate_name')) {
                $table->string('candidate_name')->nullable();
            }
            if (!Schema::hasColumn('goals', 'employee_code')) {
                $table->string('employee_code')->nullable();
            }
            if (!Schema::hasColumn('goals', 'location')) {
                $table->string('location')->nullable();
            }
            if (!Schema::hasColumn('goals', 'department')) {
                $table->string('department')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn(['candidate_name', 'employee_code', 'location', 'department']);
        });
    }
};
