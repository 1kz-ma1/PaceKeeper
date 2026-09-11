<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('onboarding_version')->default(0)->after('remember_token');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_version');
            $table->timestamp('onboarding_skipped_at')->nullable()->after('onboarding_completed_at');
        });

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_token', 120)->nullable()->index();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->index();
            $table->text('message');
            $table->string('page', 500)->nullable();
            $table->json('context')->nullable();
            $table->string('status', 24)->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_version',
                'onboarding_completed_at',
                'onboarding_skipped_at',
            ]);
        });
    }
};
