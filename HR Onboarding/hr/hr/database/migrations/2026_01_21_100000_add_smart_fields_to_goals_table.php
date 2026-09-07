<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSmartFieldsToGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goals', function (Blueprint $table) {
            if (!Schema::hasColumn('goals', 'purposes')) {
                $table->text('purposes')->nullable();
            }
            if (!Schema::hasColumn('goals', 'challenges')) {
                $table->text('challenges')->nullable();
            }
            if (!Schema::hasColumn('goals', 'completion_date')) {
                $table->date('completion_date')->nullable();
            }
            if (!Schema::hasColumn('goals', 'smart_criteria')) {
                $table->json('smart_criteria')->nullable();
            }
            if (!Schema::hasColumn('goals', 'quarterly_tracking')) {
                $table->json('quarterly_tracking')->nullable();
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
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn(['purposes', 'challenges', 'completion_date', 'smart_criteria', 'quarterly_tracking']);
        });
    }
}
