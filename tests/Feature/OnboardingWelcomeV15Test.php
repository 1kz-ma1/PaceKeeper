<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingWelcomeV15Test extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_exposes_explicit_ready_header_for_static_welcome(): void
    {
        $this->get(route('health'))
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeader('X-PaceKeeper-Ready', '1');
    }

    public function test_authenticated_user_can_complete_onboarding_once(): void
    {
        $user = User::factory()->create([
            'onboarding_version' => 0,
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.complete'))
            ->assertNoContent();

        $user->refresh();
        $this->assertSame((int) config('pacekeeper.onboarding_version'), $user->onboarding_version);
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertNull($user->onboarding_skipped_at);
    }

    public function test_authenticated_user_can_skip_onboarding_without_reappearing(): void
    {
        $user = User::factory()->create([
            'onboarding_version' => 0,
            'onboarding_skipped_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('onboarding.skip'))
            ->assertNoContent();

        $user->refresh();
        $this->assertSame((int) config('pacekeeper.onboarding_version'), $user->onboarding_version);
        $this->assertNotNull($user->onboarding_skipped_at);
        $this->assertNull($user->onboarding_completed_at);
    }

    public function test_home_marks_only_empty_dashboard_as_new_user_for_automatic_tutorial(): void
    {
        $newUser = User::factory()->create();
        $this->actingAs($newUser)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-onboarding-new-user="1"', false);

        $existingUser = User::factory()->create();
        $this->createPlan($existingUser);

        $this->actingAs($existingUser)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-onboarding-new-user="0"', false);
    }

    private function createPlan(User $user): Plan
    {
        return Plan::create([
            'user_id' => $user->id,
            'owner_token' => Str::random(64),
            'public_slug' => Str::uuid()->toString(),
            'title' => '既存の計画',
            'start_date' => today()->toDateString(),
            'deadline' => today()->addMonth()->toDateString(),
            'is_public' => false,
        ]);
    }
}
