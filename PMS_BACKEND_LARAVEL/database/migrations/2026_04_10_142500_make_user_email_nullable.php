<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUserEmailNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        } catch (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL");
            } catch (\Throwable $ex) {
                // Ignore if not supported
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
}
