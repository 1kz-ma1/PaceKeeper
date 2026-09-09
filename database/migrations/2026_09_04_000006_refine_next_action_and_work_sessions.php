<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('activation_cost')->default(3)->after('priority');
            $table->text('next_action_note')->nullable()->after('progress_reason');
        });

        Schema::table('work_sessions', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('started_at');
            $table->unsignedInteger('paused_seconds')->default(0)->after('actual_seconds');
        });

        Schema::table('work_logs', function (Blueprint $table) {
            $table->foreignId('work_session_id')
                ->nullable()
                ->after('task_id')
                ->constrained('work_sessions')
                ->nullOnDelete();
            $table->unique('work_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropUnique(['work_session_id']);
            $table->dropConstrainedForeignId('work_session_id');
        });

        Schema::table('work_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'paused_seconds']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['activation_cost', 'next_action_note']);
        });
    }
};
