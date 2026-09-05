<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppraisalTemplateToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'appraisal_template')) {
            Schema::table('users', function (Blueprint $table) {
                $table->longText('appraisal_template')->nullable()->after('permissions');
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
        if (Schema::hasColumn('users', 'appraisal_template')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('appraisal_template');
            });
        }
    }
}
