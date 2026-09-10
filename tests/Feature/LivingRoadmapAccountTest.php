<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Task;
use App\Models\User;
use App\Services\RoadmapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LivingRoadmapAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_plan_is_claimed_when_an_optional_account_is_created(): void
    {
        $plan = $this->createGuestPlan('Guest plan');

        $response = $this
            ->withCookie($this->ownerCookie($plan), $plan->owner_token)
            ->post(route('auth.register'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, $plan->fresh()->user_id);
    }

    public function test_claimed_private_plan_cannot_be_recovered_with_the_old_guest_cookie_after_logout(): void
    {
        $plan = $this->createGuestPlan('Protected plan');
        $cookieName = $this->ownerCookie($plan);

        $this
            ->withCookie($cookieName, $plan->owner_token)
            ->post(route('auth.register'), [
                'name' => 'Owner',
                'email' => 'owner@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('home'));

        $this->post(route('auth.logout'))->assertRedirect(route('home'));

        $this
            ->withCookie($cookieName, $plan->owner_token)
            ->get(route('plans.show', $plan))
            ->assertNotFound();
    }

    public function test_living_roadmap_projects_task_split_and_concrete_restart_context(): void
    {
        $plan = $this->createGuestPlan('Roadmap plan');
        $original = Task::create([
            'plan_id' => $plan->id,
            'title' => 'ログイン機能を実装',
            'estimated_minutes' => 180,
            'remaining_minutes' => 180,
            'progress_percent' => 0,
            'status' => 'todo',
            'priority' => 2,
            'activation_cost' => 3,
            'sort_order' => 1,
        ]);

        $roadmap = app(RoadmapService::class)->project($plan, [
            [
                'type' => 'update_task',
                'task_id' => $original->id,
                'title' => 'Google OAuthを実装',
                'estimated_minutes' => 90,
                'remaining_minutes' => 90,
                'progress_percent' => 0,
                'status' => 'todo',
                'next_action_note' => 'Google Cloud ConsoleでOAuthクライアントを作成するところから再開する。',
            ],
            [
                'type' => 'create_task',
                'client_ref' => 'email-auth-done',
                'title' => 'メールアドレス認証を実装',
                'estimated_minutes' => 90,
                'remaining_minutes' => 0,
                'progress_percent' => 100,
                'status' => 'done',
                'source_task_ids' => [$original->id],
                'progress_origin' => 'inherited_task',
            ],
            [
                'type' => 'reorder_tasks',
                'items' => [
                    ['task_ref' => 'email-auth-done'],
                    ['task_id' => $original->id],
                ],
            ],
        ]);

        $this->assertCount(2, $roadmap['nodes']);
        $this->assertSame('メールアドレス認証を実装', $roadmap['nodes'][0]['title']);
        $this->assertTrue($roadmap['nodes'][0]['is_lineage_child']);
        $this->assertSame('Google OAuthを実装', $roadmap['nodes'][1]['title']);
        $this->assertSame(
            'Google Cloud ConsoleでOAuthクライアントを作成するところから再開する。',
            $roadmap['nodes'][1]['next_action_note']
        );
    }

    private function createGuestPlan(string $title): Plan
    {
        return Plan::create([
            'owner_token' => Str::random(64),
            'public_slug' => Str::uuid()->toString(),
            'title' => $title,
            'start_date' => today()->toDateString(),
            'deadline' => today()->addMonth()->toDateString(),
            'is_public' => false,
        ]);
    }

    private function ownerCookie(Plan $plan): string
    {
        return 'pace_keeper_owner_token_' . $plan->id;
    }
}
