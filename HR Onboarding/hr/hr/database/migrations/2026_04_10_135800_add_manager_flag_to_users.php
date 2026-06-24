<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManagerFlagToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_manager')) {
                $table->boolean('is_manager')->default(false)->after('admin');
            }
        });

        // Seed existing managers: Anyone who has subordinates or is an admin should be a manager initially
        // OR let's just leave it false and let the user enable them.
        // Actually, to avoid breaking current view, let's enable existing managers.
        \DB::table('users')->where('admin', 1)->update(['is_manager' => true]);
        
        $managerIds = \DB::table('employees')->whereNotNull('line_manager_id')->distinct()->pluck('line_manager_id');
        \DB::table('users')->whereIn('id', $managerIds)->update(['is_manager' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_manager');
        });
    }
}
