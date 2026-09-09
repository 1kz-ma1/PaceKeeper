<?php

namespace App\Models;

use App\Enums\UserBehaviorState;
use Illuminate\Database\Eloquent\Model;

class UserStateSnapshot extends Model
{
    protected $fillable = [
        'actor_token',
        'snapshot_date',
        'action_readiness',
        'decision_load',
        'focus_continuity',
        'consistency',
        'state',
        'evidence',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'action_readiness' => 'integer',
            'decision_load' => 'integer',
            'focus_continuity' => 'integer',
            'consistency' => 'integer',
            'state' => UserBehaviorState::class,
            'evidence' => 'array',
        ];
    }
}
