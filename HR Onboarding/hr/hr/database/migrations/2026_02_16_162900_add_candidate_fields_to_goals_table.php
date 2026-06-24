<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->string('candidate_name')->nullable()->after('user_id');
            $table->string('employee_code')->nullable()->after('candidate_name');
            $table->string('location')->nullable()->after('employee_code');
            $table->string('department')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn(['candidate_name', 'employee_code', 'location', 'department']);
        });
    }
};
