<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique()->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('parent_id')->references('id')->on('departments')->onDelete('set null');
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
        Schema::dropIfExists('departments');
    }
}
