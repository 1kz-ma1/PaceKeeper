<?php

namespace App\Data;

use App\Models\Plan;
use App\Models\Task;

final readonly class TaskRecommendationData
{
    public function __construct(
        public Plan $plan,
        public Task $task,
        public array $reasons,
        public int $recommendedMinutes,
        public float $priorityScore,
        public bool $optional = false,
    ) {}

    public function toArray(): array
    {
        return [
            'plan' => $this->plan,
            'task' => $this->task,
            'reasons' => $this->reasons,
            'recommended_minutes' => $this->recommendedMinutes,
            'priority_score' => $this->priorityScore,
            'optional' => $this->optional,
        ];
    }
}
