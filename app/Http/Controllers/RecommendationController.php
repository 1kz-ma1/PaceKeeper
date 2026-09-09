<?php

namespace App\Http\Controllers;

use App\Enums\BehaviorEventType;
use App\Models\Task;
use App\Services\BehaviorEventLogger;
use App\Services\BehaviorIdentityService;
use App\Services\PlanOwnershipService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function alternative(
        Request $request,
        BehaviorIdentityService $identity,
        BehaviorEventLogger $logger,
        PlanOwnershipService $ownership,
    ) {
        $validated = $request->validate(['task_id' => ['required', 'integer', 'min:1']]);
        $task = Task::with('plan')->findOrFail($validated['task_id']);
        $ownership->authorizeTask($request, $task);
        $actorToken = $identity->resolve($request);
        $excluded = collect($request->session()->get('dashboard.recommendation_excluded', []))
            ->push($task->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $request->session()->put('dashboard.recommendation_excluded', $excluded);

        $logger->record($actorToken, BehaviorEventType::RecommendationRejected, $request, $task->plan, $task, [
            'source' => 'dashboard',
        ]);
        $logger->record($actorToken, BehaviorEventType::AlternativeRequested, $request, $task->plan, $task, [
            'source' => 'dashboard',
            'excluded_count' => count($excluded),
        ]);

        return redirect()->route('home')->with('status', '別の候補を選びました。');
    }
}
