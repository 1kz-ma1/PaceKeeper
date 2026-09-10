<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Task;
use App\Models\WorkLog;
use App\Services\PlanOwnershipService;
use Illuminate\Http\Request;

class WorkLogController extends Controller
{
    public function store(Request $request, Plan $plan)
    {
        $this->authorizePlanOwner($plan);

        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'worked_on' => ['required', 'date'],
            'actual_minutes' => ['required', 'integer', 'min:1'],
            'progress_delta_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'difficulty' => ['nullable', 'string', 'max:100'],
            'memo' => ['nullable', 'string'],
        ]);

        $task = Task::where('plan_id', $plan->id)
            ->where('id', $validated['task_id'])
            ->firstOrFail();

        WorkLog::create([
            'plan_id' => $plan->id,
            'task_id' => $task->id,
            'task_title_snapshot' => $task->title,
            'worked_on' => $validated['worked_on'],
            'actual_minutes' => $validated['actual_minutes'],
            'progress_delta_percent' => $validated['progress_delta_percent'],
            'progress_before_percent' => $task->progress_percent,
            'progress_after_percent' => min(100, $task->progress_percent + $validated['progress_delta_percent']),
            'remaining_minutes_before' => $task->remaining_minutes,
            'remaining_minutes_after' => max((int) $task->remaining_minutes - (int) $validated['actual_minutes'], 0),
            'difficulty' => $validated['difficulty'] ?? null,
            'memo' => $validated['memo'] ?? null,
        ]);

        $newProgressPercent = min(
            100,
            $task->progress_percent + $validated['progress_delta_percent']
        );

        $task->update([
            'progress_percent' => $newProgressPercent,
            'remaining_minutes' => $newProgressPercent >= 100
                ? 0
                : max((int) $task->remaining_minutes - (int) $validated['actual_minutes'], 0),
            'status' => $this->resolveTaskStatus($newProgressPercent),
        ]);

        return redirect()->route('plans.show', $plan);
    }

    public function destroy(WorkLog $workLog)
    {
        $workLog->load(['plan', 'task']);

        $this->authorizeOwner($workLog);

        $plan = $workLog->plan;
        $workLog->delete();

        return redirect()->route('plans.show', $plan);
    }

    private function authorizeOwner(WorkLog $workLog): void
    {
        $workLog->loadMissing('plan');
        app(PlanOwnershipService::class)->authorizePlan(request(), $workLog->plan);
    }

    private function resolveTaskStatus(int $progressPercent): string
    {
        if ($progressPercent >= 100) {
            return 'done';
        }

        if ($progressPercent > 0) {
            return 'doing';
        }

        return 'todo';
    }

    private function authorizePlanOwner(Plan $plan): void
    {
        app(PlanOwnershipService::class)->authorizePlan(request(), $plan);
    }
}
