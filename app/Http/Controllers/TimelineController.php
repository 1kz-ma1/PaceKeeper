<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanOwnershipService;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function index(Request $request, PlanOwnershipService $ownership)
    {
        $plans = $ownership->ownedPlans($request, [
            'workLogs' => fn ($query) => $query->with('task')->latest('worked_on')->latest('id'),
        ]);

        $items = $plans
            ->flatMap(fn ($plan) => $plan->workLogs->map(fn ($log) => ['plan' => $plan, 'log' => $log]))
            ->sortByDesc(fn (array $item) => sprintf('%s-%010d', $item['log']->worked_on?->format('Y-m-d') ?? '0000-00-00', $item['log']->id))
            ->take(60)
            ->groupBy(fn (array $item) => $item['log']->worked_on?->format('Y-m-d') ?? '日付不明');

        $categories = $plans->pluck('category')->filter()->unique()->values();
        $similarPlans = collect();

        if ($categories->isNotEmpty()) {
            $similarPlans = Plan::query()
                ->with('user')
                ->where('is_public', true)
                ->whereNotNull('public_slug')
                ->whereNotIn('id', $plans->pluck('id'))
                ->whereIn('category', $categories)
                ->latest('updated_at')
                ->take(3)
                ->get();
        }

        return view('timeline.index', compact('plans', 'items', 'similarPlans'));
    }
}
