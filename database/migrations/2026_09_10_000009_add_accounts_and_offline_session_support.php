<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->json('lineage_source_task_ids')->nullable()->after('continuation_of_task_id');
            $table->json('lineage_source_snapshots')->nullable()->after('lineage_source_task_ids');
        });

        Schema::table('work_sessions', function (Blueprint $table) {
            $table->uuid('client_session_id')->nullable()->after('browser_session_id')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            $table->dropUnique(['client_session_id']);
            $table->dropColumn('client_session_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['lineage_source_snapshots', 'lineage_source_task_ids']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
