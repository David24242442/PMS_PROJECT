<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePmsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('pms_reports')) {
            Schema::create('pms_reports', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('type');
                $table->string('department_id')->nullable();
                $table->date('date_from');
                $table->date('date_to');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
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
        Schema::dropIfExists('pms_reports');
    }
}
