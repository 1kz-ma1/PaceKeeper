<?php

namespace App\Services;

use App\Enums\BehaviorEventType;
use App\Models\BehaviorEvent;
use App\Models\Plan;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class BehaviorEventLogger
{
    public function record(
        string $actorToken,
        BehaviorEventType $type,
        Request $request,
        ?Plan $plan = null,
        ?Task $task = null,
        array $metadata = [],
        ?CarbonInterface $occurredAt = null,
    ): BehaviorEvent {
        return BehaviorEvent::create([
            'actor_token' => $actorToken,
            'event_type' => $type,
            'plan_id' => $plan?->id,
            'task_id' => $task?->id,
            'session_id' => $request->session()->getId(),
            'occurred_at' => $occurredAt ?? now(),
            'metadata' => $this->sanitizeMetadata($metadata),
        ]);
    }

    public function recordOnce(
        string $actorToken,
        BehaviorEventType $type,
        Request $request,
        ?Plan $plan = null,
        ?Task $task = null,
        array $metadata = [],
        int $withinMinutes = 30,
    ): ?BehaviorEvent {
        $exists = BehaviorEvent::query()
            ->where('actor_token', $actorToken)
            ->where('event_type', $type->value)
            ->where('session_id', $request->session()->getId())
            ->when($plan, fn ($query) => $query->where('plan_id', $plan->id))
            ->when(! $plan, fn ($query) => $query->whereNull('plan_id'))
            ->when($task, fn ($query) => $query->where('task_id', $task->id))
            ->when(! $task, fn ($query) => $query->whereNull('task_id'))
            ->where('occurred_at', '>=', now()->subMinutes($withinMinutes))
            ->exists();

        return $exists ? null : $this->record($actorToken, $type, $request, $plan, $task, $metadata);
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach (array_slice($metadata, 0, 25, true) as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = mb_substr($value, 0, 500);
            } elseif (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = array_slice($value, 0, 30);
            }
        }

        return $sanitized;
    }
}
