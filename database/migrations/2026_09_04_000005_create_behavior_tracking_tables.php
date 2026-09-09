<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_events', function (Blueprint $table) {
            $table->id();
            $table->string('actor_token', 64);
            $table->string('event_type', 64);
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 120);
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['actor_token', 'occurred_at']);
            $table->index(['actor_token', 'event_type', 'occurred_at'], 'behavior_actor_type_time_index');
            $table->index(['session_id', 'event_type']);
        });

        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('actor_token', 64);
            $table->string('browser_session_id', 120);
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('intended_minutes')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('actual_seconds')->nullable();
            $table->string('source', 32)->default('dashboard');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['actor_token', 'status']);
            $table->index(['actor_token', 'started_at']);
        });

        Schema::create('user_state_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('actor_token', 64);
            $table->date('snapshot_date');
            $table->unsignedTinyInteger('action_readiness');
            $table->unsignedTinyInteger('decision_load');
            $table->unsignedTinyInteger('focus_continuity');
            $table->unsignedTinyInteger('consistency');
            $table->string('state', 32);
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->unique(['actor_token', 'snapshot_date']);
            $table->index(['actor_token', 'snapshot_date']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('depends_on_task_id')
                ->nullable()
                ->after('plan_id')
                ->constrained('tasks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depends_on_task_id');
        });

        Schema::dropIfExists('user_state_snapshots');
        Schema::dropIfExists('work_sessions');
        Schema::dropIfExists('behavior_events');
    }
};
