<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Task;
use App\Services\PlanProgressService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PlanProgressServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_requirement_uses_explicit_remaining_minutes(): void
    {
        Carbon::setTestNow('2026-09-03 09:00:00');

        $plan = new Plan([
            'start_date' => '2026-09-01',
            'deadline' => '2026-09-07',
        ]);
        $plan->setRelation('tasks', new Collection([
            new Task(['estimated_minutes' => 600, 'remaining_minutes' => 180, 'progress_percent' => 70, 'status' => 'doing']),
            new Task(['estimated_minutes' => 200, 'remaining_minutes' => 60, 'progress_percent' => 50, 'status' => 'doing']),
            new Task(['estimated_minutes' => 999, 'remaining_minutes' => 999, 'progress_percent' => 10, 'status' => 'cancelled']),
        ]));
        $plan->setRelation('workLogs', new Collection());

        $progress = (new PlanProgressService())->calculate($plan);

        $this->assertSame(240, $progress['remaining_minutes']);
        $this->assertSame(60, $progress['daily_required_minutes']);
        $this->assertSame(65.0, $progress['weighted_progress_percent']);
    }
}
