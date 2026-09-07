<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureAllPmsTablesAndColumnsExist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Ensure 'goals' table exists
        if (!Schema::hasTable('goals')) {
            Schema::create('goals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('candidate_name')->nullable();
                $table->string('employee_code')->nullable();
                $table->string('location')->nullable();
                $table->string('department')->nullable();
                $table->string('job_title')->nullable();
                $table->string('manager_name')->nullable();
                $table->text('title')->nullable();
                $table->json('description')->nullable();
                $table->json('purposes')->nullable();
                $table->json('challenges')->nullable();
                $table->string('category')->nullable();
                $table->string('target')->nullable();
                $table->date('due_date')->nullable();
                $table->date('completion_date')->nullable();
                $table->json('smart_criteria')->nullable();
                $table->json('quarterly_tracking')->nullable();
                $table->longText('appraisal_data')->nullable();
                $table->integer('year')->default(date('Y'));
                $table->string('status')->default('assigned');
                $table->timestamp('submitted_at')->nullable();
                $table->text('hr_comments')->nullable();
                $table->decimal('overall_rating', 3, 2)->nullable();
                $table->timestamps();
            });
        } else {
            // Table exists: verify and add each missing column individually
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
                if (!Schema::hasColumn('goals', 'job_title')) {
                    $table->string('job_title')->nullable();
                }
                if (!Schema::hasColumn('goals', 'manager_name')) {
                    $table->string('manager_name')->nullable();
                }
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
                if (!Schema::hasColumn('goals', 'appraisal_data')) {
                    $table->longText('appraisal_data')->nullable();
                }
                if (!Schema::hasColumn('goals', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable();
                }
                if (!Schema::hasColumn('goals', 'hr_comments')) {
                    $table->text('hr_comments')->nullable();
                }
                if (!Schema::hasColumn('goals', 'overall_rating')) {
                    $table->decimal('overall_rating', 3, 2)->nullable();
                }
            });
        }

        // 2. Ensure 'users' table has all PMS fields
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'line_manager_id')) {
                    $table->unsignedBigInteger('line_manager_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'above_line_manager_id')) {
                    $table->unsignedBigInteger('above_line_manager_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'employee_code')) {
                    $table->string('employee_code')->nullable();
                }
                if (!Schema::hasColumn('users', 'report_to')) {
                    $table->string('report_to')->nullable();
                }
                if (!Schema::hasColumn('users', 'department')) {
                    $table->string('department')->nullable();
                }
                if (!Schema::hasColumn('users', 'location')) {
                    $table->string('location')->nullable();
                }
                if (!Schema::hasColumn('users', 'job_title')) {
                    $table->string('job_title')->nullable();
                }
                if (!Schema::hasColumn('users', 'is_manager')) {
                    $table->boolean('is_manager')->default(false);
                }
                if (!Schema::hasColumn('users', 'permissions')) {
                    $table->text('permissions')->nullable();
                }
                if (!Schema::hasColumn('users', 'appraisal_template')) {
                    $table->longText('appraisal_template')->nullable();
                }
            });
        }

        // 3. Ensure 'employees' table has line_manager_id and metadata
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'line_manager_id')) {
                    $table->unsignedBigInteger('line_manager_id')->nullable();
                }
                if (!Schema::hasColumn('employees', 'full_name')) {
                    $table->string('full_name')->nullable();
                }
                if (!Schema::hasColumn('employees', 'department')) {
                    $table->string('department')->nullable();
                }
                if (!Schema::hasColumn('employees', 'job_title')) {
                    $table->string('job_title')->nullable();
                }
            });
        }

        // 4. Ensure 'reviews' table exists
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->integer('year');
                $table->string('status')->default('draft');
                $table->text('goal_achievement')->nullable();
                $table->text('initiatives')->nullable();
                $table->text('next_steps')->nullable();
                $table->text('improvements')->nullable();
                $table->text('manager_feedback')->nullable();
                $table->date('date')->nullable();
                $table->string('emp_code')->nullable();
                $table->string('name')->nullable();
                $table->string('division')->nullable();
                $table->string('position')->nullable();
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
        // Non-destructive down method
    }
}
