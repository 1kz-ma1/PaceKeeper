<?php

namespace App\Http\Controllers;

use App\Services\CalendarPresentationService;
use App\Services\PlanOwnershipService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request, PlanOwnershipService $ownership, CalendarPresentationService $calendarService)
    {
        $plans = $ownership->ownedPlans($request, [
            'workLogs' => fn ($query) => $query->with('task')->orderBy('worked_on'),
            'tasks',
            'availabilityRules',
            'availabilityOverrides',
        ]);

        $view = $request->string('view', 'month')->toString();
        $anchor = $this->parseDate($request->string('date')->toString()) ?? Carbon::today();
        $selected = $this->parseDate($request->string('selected')->toString()) ?? Carbon::today();
        $calendar = $calendarService->calendar($plans, $view, $anchor, $selected);

        return view('calendar.index', compact('calendar', 'plans'));
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
