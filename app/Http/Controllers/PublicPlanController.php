<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanProgressService;

class PublicPlanController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_public', true)
            ->latest()
            ->get();

        return view('public_plans.index', compact('plans'));
    }

    public function show(string $publicSlug, PlanProgressService $progressService)
    {
        $plan = Plan::where('public_slug', $publicSlug)
            ->where('is_public', true)
            ->with([
                'tasks',
                'workLogs.task',
            ])
            ->firstOrFail();

        $progress = $progressService->calculate($plan);

        return view('public_plans.show', compact('plan', 'progress'));
    }
}