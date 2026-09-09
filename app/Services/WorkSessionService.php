<?php

namespace App\Services;

use App\Models\WorkSession;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class WorkSessionService
{
    public function pause(WorkSession $session): WorkSession
    {
        if ($session->status !== 'active') {
            throw ValidationException::withMessages(['session' => '作業中のセッションだけ一時停止できます。']);
        }

        $session->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        return $session->refresh();
    }

    public function resume(WorkSession $session): WorkSession
    {
        if ($session->status !== 'paused' || ! $session->paused_at) {
            throw ValidationException::withMessages(['session' => '一時停止中のセッションだけ再開できます。']);
        }

        $additionalPause = max(0, (int) $session->paused_at->diffInSeconds(now()));
        $session->update([
            'status' => 'active',
            'paused_seconds' => (int) $session->paused_seconds + $additionalPause,
            'paused_at' => null,
        ]);

        return $session->refresh();
    }

    public function finish(WorkSession $session, string $status = 'completed'): array
    {
        if (! in_array($session->status, ['active', 'paused'], true)) {
            throw ValidationException::withMessages(['session' => 'この作業セッションはすでに終了しています。']);
        }

        $endedAt = now();
        $pausedSeconds = (int) $session->paused_seconds;

        if ($session->status === 'paused' && $session->paused_at) {
            $pausedSeconds += max(0, (int) $session->paused_at->diffInSeconds($endedAt));
        }

        $wallSeconds = max(1, (int) $session->started_at->diffInSeconds($endedAt));
        $activeSeconds = max(1, $wallSeconds - $pausedSeconds);

        $session->update([
            'status' => $status,
            'ended_at' => $endedAt,
            'paused_at' => null,
            'paused_seconds' => $pausedSeconds,
            'actual_seconds' => $activeSeconds,
        ]);

        return [
            'active_seconds' => $activeSeconds,
            'wall_seconds' => $wallSeconds,
            'paused_seconds' => $pausedSeconds,
            'actual_minutes' => max(1, (int) ceil($activeSeconds / 60)),
        ];
    }

    public function activeSeconds(WorkSession $session, ?CarbonInterface $at = null): int
    {
        $at ??= now();
        $end = $session->ended_at ?? $at;
        $paused = (int) $session->paused_seconds;

        if ($session->status === 'paused' && $session->paused_at) {
            $paused += max(0, (int) $session->paused_at->diffInSeconds($at));
        }

        return max(0, (int) $session->started_at->diffInSeconds($end) - $paused);
    }
}
