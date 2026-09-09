<?php

namespace App\Data;

use App\Enums\UserBehaviorState;

final readonly class UserStateData
{
    public function __construct(
        public int $actionReadiness,
        public int $decisionLoad,
        public int $focusContinuity,
        public int $consistency,
        public UserBehaviorState $state,
        public array $evidence,
        public float $confidence,
    ) {}

    public function toArray(): array
    {
        return [
            'action_readiness' => $this->actionReadiness,
            'decision_load' => $this->decisionLoad,
            'focus_continuity' => $this->focusContinuity,
            'consistency' => $this->consistency,
            'state' => $this->state->value,
            'state_label' => $this->state->label(),
            'evidence' => $this->evidence,
            'confidence' => $this->confidence,
        ];
    }
}
