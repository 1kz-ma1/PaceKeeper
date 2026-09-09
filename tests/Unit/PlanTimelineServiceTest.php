<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\PlanAdjustment;
use App\Models\WorkLog;
use App\Services\PlanTimelineService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PlanTimelineServiceTest extends TestCase
{
    public function test_deleted_task_result_keeps_its_snapshot_in_timeline(): void
    {
        $log = new WorkLog([
            'task_title_snapshot' => '削除前のタスク名',
            'worked_on' => '2026-09-02',
            'actual_minutes' => 90,
            'progress_before_percent' => 20,
            'progress_after_percent' => 45,
            'remaining_minutes_before' => 300,
            'remaining_minutes_after' => 180,
            'outcome' => '弱点を特定した',
        ]);
        $log->setRelation('task', null);

        $adjustment = new PlanAdjustment([
            'flow' => 'plan_update',
            'summary' => '後続タスクを再編',
            'applied_operations' => [['type' => 'reorder_tasks']],
        ]);
        $adjustment->applied_at = '2026-09-03 10:00:00';

        $plan = new Plan();
        $plan->setRelation('workLogs', new Collection([$log]));
        $plan->setRelation('adjustments', new Collection([$adjustment]));

        $timeline = (new PlanTimelineService())->build($plan);
        $result = $timeline->firstWhere('type', 'result');

        $this->assertSame('削除前のタスク名', $result['title']);
        $this->assertSame(45, $result['progress_after']);
    }
}
