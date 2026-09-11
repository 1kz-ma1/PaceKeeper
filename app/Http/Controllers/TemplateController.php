<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanTemplate;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = PlanTemplate::with('templateTasks')
            ->latest()
            ->get();

        return view('templates.index', compact('templates'));
    }

    public function show(PlanTemplate $planTemplate)
    {
        $planTemplate->load('templateTasks');

        return view('templates.show', compact('planTemplate'));
    }

    public function use(Request $request, PlanTemplate $planTemplate)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:start_date'],
            'is_public' => ['nullable'],
        ]);

        $ownerToken = Str::random(64);

        $plan = Plan::create([
            'user_id' => $request->user()?->id,
            'owner_token' => $ownerToken,
            'public_slug' => Str::uuid()->toString(),
            'title' => $validated['title'],
            'description' => $planTemplate->description,
            'category' => $planTemplate->category,
            'start_date' => $validated['start_date'],
            'deadline' => $validated['deadline'],
            'is_public' => $request->boolean('is_public'),
        ]);

        foreach ($planTemplate->templateTasks()->orderBy('sort_order')->get() as $templateTask) {
            Task::create([
                'plan_id' => $plan->id,
                'title' => $templateTask->title,
                'description' => $templateTask->description,
                'estimated_minutes' => $templateTask->estimated_minutes,
                'progress_percent' => 0,
                'status' => 'todo',
                'priority' => 3,
                'sort_order' => $templateTask->sort_order,
            ]);
        }

        if (! $request->user()) {
            cookie()->queue(
                'pace_keeper_owner_token_' . $plan->id,
                $ownerToken,
                60 * 24 * 365,
                '/',
                null,
                app()->environment('production') || $request->isSecure(),
                true,
                false,
                'lax'
            );
        }

        return redirect()->route('plans.show', $plan);
    }
}