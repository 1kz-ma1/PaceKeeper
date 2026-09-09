<?php

namespace App\Data;

final readonly class UserBehaviorBaselineData
{
    public function __construct(
        public int $startLatencySeconds,
        public int $medianWorkMinutes,
        public int $usualActiveHour,
        public float $averageSelectionSteps,
        public float $workFrequency,
        public float $interruptionRate,
        public int $sampleCount,
        public float $confidence,
    ) {}

    public function toArray(): array
    {
        return [
            'start_latency_seconds' => $this->startLatencySeconds,
            'median_work_minutes' => $this->medianWorkMinutes,
            'usual_active_hour' => $this->usualActiveHour,
            'average_selection_steps' => $this->averageSelectionSteps,
            'work_frequency' => $this->workFrequency,
            'interruption_rate' => $this->interruptionRate,
            'sample_count' => $this->sampleCount,
            'confidence' => $this->confidence,
        ];
    }
}
