<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sun ... 6=Sat
            $table->unsignedSmallInteger('available_minutes')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->unique(['plan_id', 'day_of_week']);
        });

        Schema::create('plan_availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('available_minutes')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'date']);
        });

        Schema::table('work_sessions', function (Blueprint $table) {
            $table->boolean('needs_plan_update')->default(false)->after('source');
            $table->timestamp('plan_updated_at')->nullable()->after('needs_plan_update');
        });
    }

    public function down(): void
    {
        Schema::table('work_sessions', function (Blueprint $table) {
            $table->dropColumn(['needs_plan_update', 'plan_updated_at']);
        });
        Schema::dropIfExists('plan_availability_overrides');
        Schema::dropIfExists('plan_availability_rules');
    }
};
