<?php

namespace App\Services;

use App\Models\Plan;
use Carbon\Carbon;

class PlanAvailabilityService
{
    public function isConfigured(Plan $plan): bool
    {
        $plan->loadMissing(['availabilityRules', 'availabilityOverrides']);
        return $plan->availabilityRules->isNotEmpty() || $plan->availabilityOverrides->isNotEmpty();
    }

    public function minutesForDate(Plan $plan, Carbon $date): int
    {
        $plan->loadMissing(['availabilityRules', 'availabilityOverrides']);
        $override = $plan->availabilityOverrides->first(fn ($item) => $item->date?->isSameDay($date));
        if ($override) {
            return max(0, (int) $override->available_minutes);
        }

        $rule = $plan->availabilityRules->firstWhere('day_of_week', $date->dayOfWeek);
        return $rule ? max(0, (int) $rule->available_minutes) : 0;
    }

    public function capacityBetween(Plan $plan, Carbon $from, Carbon $to): int
    {
        if ($to->lt($from) || ! $this->isConfigured($plan)) {
            return 0;
        }

        $sum = 0;
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $sum += $this->minutesForDate($plan, $day);
        }
        return $sum;
    }
}
