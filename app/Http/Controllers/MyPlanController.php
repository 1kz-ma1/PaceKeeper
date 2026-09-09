<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanProgressService;
use Illuminate\Http\Request;

class MyPlanController extends Controller
{
    public function index(Request $request, PlanProgressService $progressService)
    {
        $myPlans = Plan::with(['tasks', 'workLogs'])
            ->latest()
            ->get()
            ->toBase()
            ->filter(function (Plan $plan) use ($request) {
                $cookieToken = $request->cookie('pace_keeper_owner_token_' . $plan->id);

                return $cookieToken && hash_equals($plan->owner_token, $cookieToken);
            })
            ->map(function (Plan $plan) use ($progressService) {
                return [
                    'plan' => $plan,
                    'progress' => $progressService->calculate($plan),
                ];
            });

        return view('my_plans.index', compact('myPlans'));
    }
}
