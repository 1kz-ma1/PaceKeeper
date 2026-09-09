<?php

namespace App\Models;

use App\Enums\BehaviorEventType;
use Illuminate\Database\Eloquent\Model;

class BehaviorEvent extends Model
{
    protected $fillable = [
        'actor_token',
        'event_type',
        'plan_id',
        'task_id',
        'session_id',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => BehaviorEventType::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
