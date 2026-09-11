<?php

namespace App\Services;

use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarPresentationService
{
    public function __construct(
        private readonly PlanAvailabilityService $availabilityService,
        private readonly PlanProgressService $progressService,
    ) {}

    public function weekSummary(Collection $plans, ?Carbon $anchor = null): array
    {
        $anchor = ($anchor ?? Carbon::today())->copy()->startOfDay();
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);

        return [
            'start' => $start,
            'end' => $end,
            'days' => $this->buildDays($plans, $start, $end),
        ];
    }

    public function calendar(Collection $plans, string $view = 'month', ?Carbon $anchor = null, ?Carbon $selected = null): array
    {
        $anchor = ($anchor ?? Carbon::today())->copy()->startOfDay();
        $selected = ($selected ?? Carbon::today())->copy()->startOfDay();
        $view = in_array($view, ['month', 'week'], true) ? $view : 'month';

        if ($view === 'week') {
            $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $end = $start->copy()->addDays(6);
        } else {
            $start = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $end = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        }

        $days = $this->buildDays($plans, $start, $end);
        $selectedDay = collect($days)->first(fn (array $day) => $day['date']->isSameDay($selected));

        return [
            'view' => $view,
            'anchor' => $anchor,
            'start' => $start,
            'end' => $end,
            'days' => $days,
            'selected' => $selected,
            'selected_day' => $selectedDay,
            'configured_plan_count' => $plans->filter(fn (Plan $plan) => $this->availabilityService->isConfigured($plan))->count(),
        ];
    }

    private function buildDays(Collection $plans, Carbon $start, Carbon $end): array
    {
        $planMeta = $plans->mapWithKeys(function (Plan $plan) {
            $plan->loadMissing(['workLogs', 'availabilityRules', 'availabilityOverrides']);
            $progress = $this->progressService->calculate($plan);

            return [$plan->id => [
                'plan' => $plan,
                'progress' => $progress,
                'configured' => (bool) ($progress['availability_configured'] ?? false),
                'ratio' => $progress['required_capacity_ratio'],
            ]];
        });

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $breakdown = $planMeta->map(function (array $meta) use ($date) {
                /** @var Plan $plan */
                $plan = $meta['plan'];
                $available = $meta['configured'] ? $this->availabilityService->minutesForDate($plan, $date) : 0;
                $actual = (int) $plan->workLogs
                    ->filter(fn ($log) => $log->worked_on?->isSameDay($date))
                    ->sum('actual_minutes');
                $ratio = $meta['ratio'];
                $recommended = $meta['configured'] && $ratio !== null
                    ? (int) ceil($available * max(0, (float) $ratio))
                    : 0;

                return [
                    'plan' => $plan,
                    'available_minutes' => $available,
                    'actual_minutes' => $actual,
                    'recommended_minutes' => $recommended,
                    'remaining_minutes' => max(0, $available - $actual),
                    'load_ratio' => $ratio,
                ];
            })->values();

            $availableTotal = (int) $breakdown->sum('available_minutes');
            $actualTotal = (int) $breakdown->sum('actual_minutes');
            $recommendedTotal = (int) $breakdown->sum('recommended_minutes');

            $days[] = [
                'date' => $date->copy(),
                'is_today' => $date->isToday(),
                'is_past' => $date->isPast() && ! $date->isToday(),
                'available_minutes' => $availableTotal,
                'actual_minutes' => $actualTotal,
                'remaining_minutes' => max(0, $availableTotal - $actualTotal),
                'recommended_minutes' => $recommendedTotal,
                'shortage_minutes' => max(0, $recommendedTotal - $availableTotal),
                'plans' => $breakdown,
            ];
        }

        return $days;
    }
}
