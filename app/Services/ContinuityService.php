<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\WorkSession;
use Illuminate\Support\Collection;

class ContinuityService
{
    public function forPlans(Collection $plans, string $actorToken): ?array
    {
        $accountPlanIds = $plans
            ->filter(fn (Plan $plan) => $plan->user_id !== null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $guestPlanIds = $plans
            ->filter(fn (Plan $plan) => $plan->user_id === null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($accountPlanIds === [] && $guestPlanIds === []) {
            return null;
        }

        $session = WorkSession::with(['plan', 'task'])
            ->where(function ($query) use ($accountPlanIds, $guestPlanIds, $actorToken) {
                if ($accountPlanIds !== []) {
                    $query->whereIn('plan_id', $accountPlanIds);
                }

                if ($guestPlanIds !== []) {
                    $method = $accountPlanIds !== [] ? 'orWhere' : 'where';
                    $query->{$method}(function ($guestQuery) use ($guestPlanIds, $actorToken) {
                        $guestQuery
                            ->whereIn('plan_id', $guestPlanIds)
                            ->where('actor_token', $actorToken);
                    });
                }
            })
            ->latest('started_at')
            ->first();

        return $session ? $this->fromSession($session) : null;
    }

    public function forPlan(Plan $plan, string $actorToken): ?array
    {
        $query = WorkSession::with(['plan', 'task'])
            ->where('plan_id', $plan->id);

        if ($plan->user_id === null) {
            $query->where('actor_token', $actorToken);
        }

        $session = $query->latest('started_at')->first();

        return $session ? $this->fromSession($session) : null;
    }

    private function fromSession(WorkSession $session): array
    {
        $task = $session->task;
        $active = in_array($session->status, ['active', 'paused'], true);
        $taskStartable = $task && ! in_array($task->status, ['done', 'cancelled'], true);

        return [
            'session_id' => $session->id,
            'plan_id' => $session->plan_id,
            'plan_title' => $session->plan?->title,
            'plan_icon' => $session->plan?->displayIcon() ?? '🧭',
            'plan_accent' => $session->plan?->accentKey() ?? 'sky',
            'plan_world' => $session->plan?->roadmapWorld() ?? 'default',
            'task_id' => $session->task_id,
            'task_title' => $task?->title ?? '前回の作業',
            'next_action_note' => $task?->next_action_note,
            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
            'status' => $session->status,
            'is_active' => $active,
            'can_resume_task' => $taskStartable,
            'needs_plan_update' => (bool) $session->needs_plan_update,
        ];
    }
}
