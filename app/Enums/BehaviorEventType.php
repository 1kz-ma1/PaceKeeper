<?php

namespace App\Enums;

enum BehaviorEventType: string
{
    case DashboardViewed = 'dashboard_viewed';
    case DashboardIdle = 'dashboard_idle';
    case PlanTabViewed = 'plan_tab_viewed';
    case TaskViewed = 'task_viewed';
    case TaskStarted = 'task_started';
    case WorkStarted = 'work_started';
    case WorkCompleted = 'work_completed';
    case WorkInterrupted = 'work_interrupted';
    case RecommendationShown = 'recommendation_shown';
    case RecommendationAccepted = 'recommendation_accepted';
    case RecommendationRejected = 'recommendation_rejected';
    case AlternativeRequested = 'alternative_requested';
    case NavigationStarted = 'navigation_started';
    case NavigationCompleted = 'navigation_completed';

    public static function clientRecordable(): array
    {
        return [
            self::DashboardIdle->value,
            self::PlanTabViewed->value,
            self::TaskViewed->value,
        ];
    }
}
