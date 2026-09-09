<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('remaining_minutes')->nullable()->after('estimated_minutes');
            $table->text('progress_reason')->nullable()->after('progress_percent');
        });

        DB::table('tasks')
            ->select(['id', 'estimated_minutes', 'progress_percent', 'status'])
            ->orderBy('id')
            ->eachById(function ($task) {
                $remainingMinutes = $task->status === 'cancelled'
                    ? 0
                    : (int) round($task->estimated_minutes * (100 - $task->progress_percent) / 100);

                DB::table('tasks')->where('id', $task->id)->update([
                    'remaining_minutes' => max($remainingMinutes, 0),
                ]);
            });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->string('task_title_snapshot')->nullable()->after('task_id');
            $table->unsignedTinyInteger('progress_before_percent')->nullable()->after('progress_delta_percent');
            $table->unsignedTinyInteger('progress_after_percent')->nullable()->after('progress_before_percent');
            $table->unsignedInteger('remaining_minutes_before')->nullable()->after('progress_after_percent');
            $table->unsignedInteger('remaining_minutes_after')->nullable()->after('remaining_minutes_before');
            $table->text('outcome')->nullable()->after('memo');
        });

        DB::table('work_logs')
            ->whereNotNull('task_id')
            ->select(['id', 'task_id'])
            ->orderBy('id')
            ->eachById(function ($workLog) {
                DB::table('work_logs')->where('id', $workLog->id)->update([
                    'task_title_snapshot' => DB::table('tasks')->where('id', $workLog->task_id)->value('title'),
                ]);
            });

        Schema::table('plan_adjustments', function (Blueprint $table) {
            $table->string('flow')->default('plan_update')->after('plan_id');
            $table->json('metrics_before')->nullable()->after('applied_operations');
            $table->json('metrics_after')->nullable()->after('metrics_before');
        });
    }

    public function down(): void
    {
        Schema::table('plan_adjustments', function (Blueprint $table) {
            $table->dropColumn(['flow', 'metrics_before', 'metrics_after']);
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropColumn([
                'task_title_snapshot',
                'progress_before_percent',
                'progress_after_percent',
                'remaining_minutes_before',
                'remaining_minutes_after',
                'outcome',
            ]);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['remaining_minutes', 'progress_reason']);
        });
    }
};
