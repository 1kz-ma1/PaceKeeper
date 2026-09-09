<?php

namespace Tests\Feature;

use App\Data\UserStateData;
use App\Enums\BehaviorEventType;
use App\Enums\UserBehaviorState;
use App\Models\BehaviorEvent;
use App\Models\Plan;
use App\Models\Task;
use App\Models\WorkLog;
use App\Models\WorkSession;
use App\Services\RecommendationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-04 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_normal_recommendation_prefers_the_higher_priority_task(): void
    {
        $plan = $this->createPlan('Normal', -1, 14);
        $low = $this->createTask($plan, 'Low priority', 60, 5);
        $high = $this->createTask($plan, 'High priority', 60, 1);

        $result = $this->recommend([$plan]);

        $this->assertSame($high->id, $result?->task->id);
        $this->assertNotSame($low->id, $result?->task->id);
        $this->assertNotEmpty($result?->reasons);
    }

    public function test_a_behind_plan_contributes_an_explainable_reason(): void
    {
        $plan = $this->createPlan('Behind', -10, 10);
        $this->createTask($plan, 'Catch up', 120, 3, progress: 0);

        $result = $this->recommend([$plan]);

        $this->assertSame('Catch up', $result?->task->title);
        $this->assertContains('実績進捗が期待進捗を下回っているため', $result?->reasons ?? []);
    }

    public function test_multiple_plans_are_ranked_instead_of_always_using_collection_order(): void
    {
        $farPlan = $this->createPlan('Far', -1, 30);
        $this->createTask($farPlan, 'Far task', 30, 3);
        $urgentPlan = $this->createPlan('Urgent', -10, 1);
        $urgent = $this->createTask($urgentPlan, 'Urgent task', 30, 3);

        $result = $this->recommend([$farPlan, $urgentPlan]);

        $this->assertSame($urgent->id, $result?->task->id);
    }

    public function test_recommendation_is_marked_optional_after_enough_work_today(): void
    {
        $plan = $this->createPlan('Enough', -1, 5);
        $task = $this->createTask($plan, 'Extra task', 40, 3);
        WorkLog::create([
            'plan_id' => $plan->id,
            'task_id' => $task->id,
            'task_title_snapshot' => $task->title,
            'worked_on' => today(),
            'actual_minutes' => 10,
            'progress_delta_percent' => 0,
        ]);

        $result = $this->recommend([$plan]);

        $this->assertTrue($result?->optional);
        $this->assertStringContainsString('今日必要な作業量には到達済み', $result?->reasons[0] ?? '');
    }

    public function test_short_time_budget_prefers_a_task_that_fits(): void
    {
        $plan = $this->createPlan('Short', -1, 20);
        $long = $this->createTask($plan, 'Long', 120, 3);
        $short = $this->createTask($plan, 'Short', 10, 3);

        $result = $this->recommend([$plan], timeBudgetMinutes: 15);

        $this->assertSame($short->id, $result?->task->id);
        $this->assertNotSame($long->id, $result?->task->id);
        $this->assertSame(10, $result?->recommendedMinutes);
    }

    public function test_low_readiness_state_favors_a_short_start(): void
    {
        $plan = $this->createPlan('Low readiness', -1, 20);
        $this->createTask($plan, 'Long', 90, 3, activationCost: 5);
        $short = $this->createTask($plan, 'Ten minutes', 10, 3, activationCost: 1);

        $result = $this->recommend([$plan], state: $this->state(UserBehaviorState::LowReadiness));

        $this->assertSame($short->id, $result?->task->id);
        $this->assertContains('今は始めやすい作業から着手しやすいため', $result?->reasons ?? []);
    }


    public function test_low_readiness_prefers_lower_activation_cost_when_time_and_priority_are_equal(): void
    {
        $plan = $this->createPlan('Activation', -1, 20);
        $heavy = $this->createTask($plan, 'Heavy start', 20, 3, activationCost: 5);
        $easy = $this->createTask($plan, 'Easy start', 20, 3, activationCost: 1);

        $result = $this->recommend([$plan], state: $this->state(UserBehaviorState::LowReadiness), timeBudgetMinutes: 30);

        $this->assertSame($easy->id, $result?->task->id);
        $this->assertNotSame($heavy->id, $result?->task->id);
    }

    public function test_rejected_task_is_excluded_from_the_next_recommendation(): void
    {
        $plan = $this->createPlan('Alternatives', -1, 20);
        $first = $this->createTask($plan, 'First', 30, 1);
        $second = $this->createTask($plan, 'Second', 30, 2);
        $initial = $this->recommend([$plan]);
        $alternative = $this->recommend([$plan], excludedTaskIds: [$initial->task->id]);

        $this->assertSame($first->id, $initial->task->id);
        $this->assertSame($second->id, $alternative?->task->id);
    }

    public function test_completed_and_blocked_tasks_are_not_recommended(): void
    {
        $plan = $this->createPlan('Eligibility', -1, 20);
        $done = $this->createTask($plan, 'Done', 10, 1, status: 'done', progress: 100);
        $prerequisite = $this->createTask($plan, 'Prerequisite', 40, 5);
        $blocked = $this->createTask($plan, 'Blocked', 10, 1, dependsOn: $prerequisite->id);
        $eligible = $this->createTask($plan, 'Eligible', 30, 3);

        $result = $this->recommend([$plan], excludedTaskIds: [$prerequisite->id]);

        $this->assertSame($eligible->id, $result?->task->id);
        $this->assertNotSame($done->id, $result?->task->id);
        $this->assertNotSame($blocked->id, $result?->task->id);
    }


    public function test_recent_completed_session_is_a_next_action_signal_without_manual_outcome_metadata(): void
    {
        $actorToken = Str::random(64);
        $continuedPlan = $this->createPlan('Continue plan', -1, 20);
        $continued = $this->createTask($continuedPlan, 'Keep going', 30, 3);
        $otherPlan = $this->createPlan('Other plan', -1, 20);
        $other = $this->createTask($otherPlan, 'Other same priority', 30, 3);

        WorkSession::create([
            'actor_token' => $actorToken,
            'browser_session_id' => 'continue-session',
            'plan_id' => $continuedPlan->id,
            'task_id' => $continued->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'ended_at' => now()->subMinutes(5),
            'actual_seconds' => 300,
            'paused_seconds' => 0,
            'source' => 'dashboard',
        ]);

        $plans = collect([
            $continuedPlan->fresh(['tasks', 'workLogs']),
            $otherPlan->fresh(['tasks', 'workLogs']),
        ]);
        $result = app(RecommendationService::class)->recommend(
            $plans,
            $this->state(UserBehaviorState::Normal),
            actorToken: $actorToken,
        );

        $this->assertSame($continued->id, $result?->task->id);
        $this->assertNotSame($other->id, $result?->task->id);
        $this->assertContains('前回の続きで再開しやすいため', $result?->reasons ?? []);
    }

    public function test_accepted_history_is_aggregated_as_scalar_counts_without_an_eloquent_collection_error(): void
    {
        $actorToken = Str::random(64);
        $plan = $this->createPlan('Accepted history', -1, 20);
        $accepted = $this->createTask($plan, 'Accepted before', 30, 3);
        $other = $this->createTask($plan, 'Other', 30, 3);

        foreach ([now()->subDays(2), now()->subDay()] as $occurredAt) {
            BehaviorEvent::create([
                'actor_token' => $actorToken,
                'event_type' => BehaviorEventType::RecommendationAccepted,
                'plan_id' => $plan->id,
                'task_id' => $accepted->id,
                'session_id' => 'recommendation-history-session',
                'occurred_at' => $occurredAt,
                'metadata' => [],
            ]);
        }

        $loadedPlans = collect([$plan->fresh(['tasks', 'workLogs'])]);
        $result = app(RecommendationService::class)->recommend(
            $loadedPlans,
            $this->state(UserBehaviorState::Normal),
            actorToken: $actorToken,
        );

        $this->assertSame($accepted->id, $result?->task->id);
        $this->assertNotSame($other->id, $result?->task->id);
    }


    public function test_personal_choice_history_can_break_a_tie_between_equivalent_tasks(): void
    {
        $actorToken = Str::random(64);
        $plan = $this->createPlan('Personalized', -1, 20);
        $oftenAccepted = $this->createTask($plan, 'Often accepted', 30, 3);
        $oftenRejected = $this->createTask($plan, 'Often rejected', 30, 3);

        foreach (range(1, 3) as $index) {
            BehaviorEvent::create([
                'actor_token' => $actorToken,
                'event_type' => BehaviorEventType::RecommendationAccepted,
                'plan_id' => $plan->id,
                'task_id' => $oftenAccepted->id,
                'session_id' => 'accepted-' . $index,
                'occurred_at' => now()->subDays($index),
                'metadata' => ['recommended_minutes' => 30],
            ]);
        }
        foreach (range(1, 2) as $index) {
            BehaviorEvent::create([
                'actor_token' => $actorToken,
                'event_type' => BehaviorEventType::RecommendationRejected,
                'plan_id' => $plan->id,
                'task_id' => $oftenRejected->id,
                'session_id' => 'rejected-' . $index,
                'occurred_at' => now()->subDays($index),
                'metadata' => [],
            ]);
        }

        $result = app(RecommendationService::class)->recommend(
            collect([$plan->fresh(['tasks', 'workLogs'])]),
            $this->state(UserBehaviorState::Normal),
            timeBudgetMinutes: 30,
            actorToken: $actorToken,
        );

        $this->assertSame($oftenAccepted->id, $result?->task->id);
    }

    public function test_personal_start_latency_can_lower_effective_activation_cost(): void
    {
        $actorToken = Str::random(64);
        $plan = $this->createPlan('Activation learning', -1, 20);
        $easyForUser = $this->createTask($plan, 'Easy for this user', 30, 3, activationCost: 3);
        $other = $this->createTask($plan, 'Other', 30, 3, activationCost: 3);

        foreach ([20, 25, 180, 200] as $index => $latency) {
            $task = $index < 2 ? $easyForUser : $other;
            BehaviorEvent::create([
                'actor_token' => $actorToken,
                'event_type' => BehaviorEventType::WorkStarted,
                'plan_id' => $plan->id,
                'task_id' => $task->id,
                'session_id' => 'latency-' . $index,
                'occurred_at' => now()->subDays($index + 1),
                'metadata' => ['start_latency_seconds' => $latency, 'source' => 'dashboard'],
            ]);
        }

        $profile = app(\App\Services\RecommendationPersonalizationService::class)->profile($actorToken);

        $this->assertSame(2, app(\App\Services\RecommendationPersonalizationService::class)->effectiveActivationCost($easyForUser, $profile));
        $this->assertGreaterThanOrEqual(3, app(\App\Services\RecommendationPersonalizationService::class)->effectiveActivationCost($other, $profile));
    }

    public function test_continuation_task_uses_the_parent_session_as_a_strong_next_action_signal(): void
    {
        $actorToken = Str::random(64);
        $plan = $this->createPlan('Continuation', -1, 20);
        $parent = $this->createTask($plan, 'Original segment', 30, 3, status: 'done', progress: 100);
        $next = Task::create([
            'plan_id' => $plan->id,
            'continuation_of_task_id' => $parent->id,
            'title' => 'Concrete next step',
            'estimated_minutes' => 25,
            'remaining_minutes' => 25,
            'progress_percent' => 0,
            'status' => 'todo',
            'priority' => 3,
            'activation_cost' => 3,
            'sort_order' => 2,
        ]);
        $otherPlan = $this->createPlan('Other', -1, 20);
        $other = $this->createTask($otherPlan, 'Other high priority', 25, 1);

        WorkSession::create([
            'actor_token' => $actorToken,
            'browser_session_id' => 'checkpoint-parent',
            'plan_id' => $plan->id,
            'task_id' => $parent->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'ended_at' => now()->subMinutes(5),
            'actual_seconds' => 1500,
            'paused_seconds' => 0,
            'source' => 'dashboard',
        ]);

        $result = app(RecommendationService::class)->recommend(
            collect([
                $plan->fresh(['tasks', 'workLogs']),
                $otherPlan->fresh(['tasks', 'workLogs']),
            ]),
            $this->state(UserBehaviorState::Normal),
            actorToken: $actorToken,
        );

        $this->assertSame($next->id, $result?->task->id);
        $this->assertNotSame($other->id, $result?->task->id);
        $this->assertContains('前回の作業結果から切り出した次のActionだから', $result?->reasons ?? []);
    }

    private function recommend(
        array $plans,
        ?UserStateData $state = null,
        ?int $timeBudgetMinutes = null,
        array $excludedTaskIds = [],
    ) {
        $loadedPlans = collect($plans)->map(fn (Plan $plan) => $plan->fresh(['tasks', 'workLogs']));

        return app(RecommendationService::class)->recommend(
            $loadedPlans,
            $state ?? $this->state(UserBehaviorState::Normal),
            timeBudgetMinutes: $timeBudgetMinutes,
            excludedTaskIds: $excludedTaskIds,
        );
    }

    private function state(UserBehaviorState $state): UserStateData
    {
        return new UserStateData(60, 30, 55, 50, $state, [], 0.5);
    }

    private function createPlan(string $title, int $startsInDays, int $deadlineInDays): Plan
    {
        return Plan::create([
            'owner_token' => Str::random(64),
            'public_slug' => (string) Str::uuid(),
            'title' => $title,
            'start_date' => today()->addDays($startsInDays),
            'deadline' => today()->addDays($deadlineInDays),
            'is_public' => false,
        ]);
    }

    private function createTask(
        Plan $plan,
        string $title,
        int $minutes,
        int $priority,
        string $status = 'todo',
        int $progress = 0,
        ?int $dependsOn = null,
        int $activationCost = 3,
    ): Task {
        return Task::create([
            'plan_id' => $plan->id,
            'depends_on_task_id' => $dependsOn,
            'title' => $title,
            'estimated_minutes' => $minutes,
            'remaining_minutes' => $status === 'done' ? 0 : $minutes,
            'progress_percent' => $progress,
            'status' => $status,
            'priority' => $priority,
            'activation_cost' => $activationCost,
            'sort_order' => Task::where('plan_id', $plan->id)->count() + 1,
        ]);
    }
}
