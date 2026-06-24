<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPmsFieldsToGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goals', function (Blueprint $table) {
            if (!Schema::hasColumn('goals', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('appraisal_data');
            }
            if (!Schema::hasColumn('goals', 'hr_comments')) {
                $table->text('hr_comments')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('goals', 'overall_rating')) {
                $table->decimal('overall_rating', 3, 2)->nullable()->after('hr_comments');
            }
        });
    }

    public function down()
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn(['submitted_at', 'hr_comments', 'overall_rating']);
        });
    }
}
