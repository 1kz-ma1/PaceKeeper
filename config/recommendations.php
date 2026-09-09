<?php

return [
    'weights' => [
        'task_priority' => 8.0,
        'behind_schedule' => 0.45,
        'deadline_urgency' => 18.0,
        'stale_plan' => 1.5,
        'time_fit' => 14.0,
        'time_over_budget_penalty' => 8.0,
        'recently_worked_penalty' => 6.0,
        'accepted_before' => 3.0,
        'activation_fit' => 4.0,
        'recent_continuation' => 14.0,
    ],
    'default_minutes' => 25,
    'low_readiness_minutes' => 10,
    'high_focus_minutes' => 45,
    'baseline_days' => 28,
    'state_window_days' => 14,
    'min_focus_session_seconds' => 120,
    'analysis_min_samples' => 5,
    'analysis_min_days' => 3,
    'trend_min_days' => 3,
    'personalization_days' => 90,
    'personalization_weight' => 1.0,
];
