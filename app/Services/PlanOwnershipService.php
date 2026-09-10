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
        $userId = $request->user()?->id;
        $guestPlanIds = $this->guestPlanIds($request);

        if (! $userId && $guestPlanIds === []) {
            return collect();
        }

        $query = Plan::with($with)->latest();

        if ($userId) {
            $query->where(function ($query) use ($userId, $guestPlanIds) {
                $query->where('user_id', $userId);
                if ($guestPlanIds !== []) {
                    $query->orWhere(function ($guestQuery) use ($guestPlanIds) {
                        $guestQuery->whereNull('user_id')->whereIn('id', $guestPlanIds);
                    });
                }
            });
        } else {
            $query->whereNull('user_id')->whereIn('id', $guestPlanIds);
        }

        return $query->get()
            ->toBase()
            ->filter(fn (Plan $plan) => $this->owns($request, $plan))
            ->values();
    }

    public function owns(Request $request, Plan $plan): bool
    {
        if ($plan->user_id !== null) {
            return $request->user() && (int) $plan->user_id === (int) $request->user()->id;
        }

        $token = $request->cookie('pace_keeper_owner_token_' . $plan->id);

        return is_string($token)
            && $token !== ''
            && is_string($plan->owner_token)
            && hash_equals($plan->owner_token, $token);
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

    /** @return array<int> */
    private function guestPlanIds(Request $request): array
    {
        $ids = [];
        foreach (array_keys($request->cookies->all()) as $name) {
            if (preg_match('/^pace_keeper_owner_token_(\d+)$/', (string) $name, $matches)) {
                $ids[] = (int) $matches[1];
            }
        }

        return array_values(array_unique(array_filter($ids, fn (int $id) => $id > 0)));
    }
}
