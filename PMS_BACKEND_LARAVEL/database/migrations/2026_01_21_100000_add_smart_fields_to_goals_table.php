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
            $table->text('purposes')->nullable()->after('description');
            $table->text('challenges')->nullable()->after('purposes');
            $table->date('completion_date')->nullable()->after('due_date');
            $table->json('smart_criteria')->nullable()->after('completion_date');
            $table->json('quarterly_tracking')->nullable()->after('smart_criteria');
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
