<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Task;
use App\Services\PlanProgressService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AvailabilityPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_target_uses_available_capacity_when_schedule_is_configured(): void
    {
        Carbon::setTestNow('2026-09-09 09:00:00'); // Wednesday

        $plan = Plan::create([
            'owner_token' => Str::random(64),
            'title' => 'Calendar plan',
            'start_date' => '2026-09-01',
            'deadline' => '2026-09-11',
            'is_public' => false,
        ]);

        Task::create([
            'plan_id' => $plan->id,
            'title' => 'Remaining work',
            'estimated_minutes' => 300,
            'remaining_minutes' => 180,
            'progress_percent' => 40,
            'status' => 'doing',
            'priority' => 2,
            'activation_cost' => 3,
            'sort_order' => 1,
        ]);

        // Wed 120 + Thu 60 + Fri 120 = 300 minutes of capacity.
        foreach ([[3, 120], [4, 60], [5, 120]] as [$day, $minutes]) {
            $plan->availabilityRules()->create([
                'day_of_week' => $day,
                'available_minutes' => $minutes,
            ]);
        }

        $progress = app(PlanProgressService::class)->calculate($plan->fresh());

        $this->assertTrue($progress['availability_configured']);
        $this->assertSame(300, $progress['remaining_available_minutes']);
        $this->assertSame(120, $progress['today_available_minutes']);
        $this->assertSame(72, $progress['daily_required_minutes']); // 120 * (180 / 300)
        $this->assertSame(0.6, $progress['required_capacity_ratio']);
    }

    public function test_date_override_replaces_weekly_capacity(): void
    {
        Carbon::setTestNow('2026-09-09 09:00:00');

        $plan = Plan::create([
            'owner_token' => Str::random(64),
            'title' => 'Override plan',
            'start_date' => '2026-09-01',
            'deadline' => '2026-09-09',
            'is_public' => false,
        ]);
        Task::create([
            'plan_id' => $plan->id,
            'title' => 'Work',
            'estimated_minutes' => 120,
            'remaining_minutes' => 120,
            'progress_percent' => 0,
            'status' => 'todo',
            'priority' => 2,
            'activation_cost' => 3,
            'sort_order' => 1,
        ]);
        $plan->availabilityRules()->create(['day_of_week' => 3, 'available_minutes' => 120]);
        $plan->availabilityOverrides()->create([
            'date' => '2026-09-09',
            'available_minutes' => 30,
            'note' => '予定あり',
        ]);

        $progress = app(PlanProgressService::class)->calculate($plan->fresh());

        $this->assertSame(30, $progress['today_available_minutes']);
        $this->assertSame(30, $progress['remaining_available_minutes']);
        $this->assertSame('作業時間不足', $progress['status']);
    }
}
