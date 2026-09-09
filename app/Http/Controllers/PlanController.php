<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanProgressService;
use App\Services\PlanTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function create()
    {
        return view('plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:start_date'],
            'is_public' => ['nullable'],
        ]);

        $ownerToken = Str::random(64);

        $plan = Plan::create([
            'owner_token' => $ownerToken,
            'public_slug' => Str::uuid()->toString(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'start_date' => $validated['start_date'],
            'deadline' => $validated['deadline'],
            'is_public' => $request->boolean('is_public'),
        ]);

        cookie()->queue(
            'pace_keeper_owner_token_' . $plan->id,
            $ownerToken,
            60 * 24 * 365
        );

        return redirect()
            ->route('plans.ai_task_assistant.show', $plan)
            ->with('status', '計画の基本情報を作成しました。続けてAIで初期タスクを生成できます。');
    }

    public function show(Plan $plan, PlanProgressService $progressService, PlanTimelineService $timelineService)
    {
        $ownerToken = request()->cookie('pace_keeper_owner_token_' . $plan->id);

        $canEdit = $ownerToken && hash_equals($plan->owner_token, $ownerToken);

        $plan->load([
            'tasks' => fn ($query) => $query->with('prerequisite')->orderBy('sort_order')->orderBy('id'),
            'workLogs' => fn ($query) => $query->with('task')->latest('worked_on')->latest('id'),
            'adjustments' => fn ($query) => $query->latest('applied_at')->limit(10),
            'availabilityRules',
            'availabilityOverrides',
        ]);

        $progress = $progressService->calculate($plan);
        $timeline = $timelineService->build($plan);

        return view('plans.show', compact('plan', 'progress', 'timeline', 'canEdit'));
    }

    public function update(Request $request, Plan $plan)
    {
        $this->authorizeOwner($plan);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'deadline' => ['required', 'date', 'after_or_equal:start_date'],
            'is_public' => ['nullable'],
        ]);

        $plan->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'start_date' => $validated['start_date'],
            'deadline' => $validated['deadline'],
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()->route('plans.show', $plan);
    }

    public function destroy(Plan $plan)
    {
        $this->authorizeOwner($plan);

        $plan->delete();

        return redirect()->route('home');
    }

    private function authorizeOwner(Plan $plan): void
    {
        $ownerToken = request()->cookie('pace_keeper_owner_token_' . $plan->id);

        if (! $ownerToken || ! hash_equals($plan->owner_token, $ownerToken)) {
            abort(403, 'この計画を編集する権限がありません。');
        }
    }

    public function edit(Plan $plan)
    {
        $this->authorizeOwner($plan);

        return view('plans.edit', compact('plan'));
    }
}
