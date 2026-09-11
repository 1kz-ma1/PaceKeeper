<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCalendarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_navigation_surfaces_are_available_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Plan::create([
            'user_id' => $user->id,
            'owner_token' => str_repeat('a', 64),
            'public_slug' => fake()->uuid(),
            'title' => 'Navigation Plan',
            'start_date' => today()->subDay(),
            'deadline' => today()->addMonth(),
            'is_public' => false,
        ]);

        $this->actingAs($user)->get(route('home'))->assertOk();
        $this->actingAs($user)->get(route('roadmap.index'))->assertOk();
        $this->actingAs($user)->get(route('calendar.index'))->assertOk();
        $this->actingAs($user)->get(route('timeline.index'))->assertOk();
    }

    public function test_plan_edit_prefills_dates_and_missing_date_fields_keep_existing_values(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'user_id' => $user->id,
            'owner_token' => str_repeat('b', 64),
            'public_slug' => fake()->uuid(),
            'title' => 'Preserve Dates',
            'start_date' => '2026-09-01',
            'deadline' => '2026-11-30',
            'is_public' => false,
        ]);

        $this->actingAs($user)
            ->get(route('plans.edit', $plan))
            ->assertOk()
            ->assertSee('value="2026-09-01"', false)
            ->assertSee('value="2026-11-30"', false);

        $this->actingAs($user)->put(route('plans.update', $plan), [
            'title' => 'Preserve Dates Updated',
        ])->assertRedirect(route('plans.show', $plan));

        $plan->refresh();
        $this->assertSame('2026-09-01', $plan->start_date->format('Y-m-d'));
        $this->assertSame('2026-11-30', $plan->deadline->format('Y-m-d'));
    }
}
