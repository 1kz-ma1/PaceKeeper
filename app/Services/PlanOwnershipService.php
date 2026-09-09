<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PlanOwnershipService
{
    public function ownedPlans(Request $request, array $with = []): Collection
    {
        return Plan::with($with)
            ->latest()
            ->get()
            ->toBase()
            ->filter(fn (Plan $plan) => $this->owns($request, $plan))
            ->values();
    }

    public function owns(Request $request, Plan $plan): bool
    {
        $token = $request->cookie('pace_keeper_owner_token_' . $plan->id);

        return is_string($token) && $token !== '' && hash_equals($plan->owner_token, $token);
    }

    public function authorizePlan(Request $request, Plan $plan): void
    {
        if (! $this->owns($request, $plan)) {
            abort(403, 'この計画を操作する権限がありません。');
        }
    }

    public function authorizeTask(Request $request, Task $task): void
    {
        $task->loadMissing('plan');
        $this->authorizePlan($request, $task->plan);
    }
}
