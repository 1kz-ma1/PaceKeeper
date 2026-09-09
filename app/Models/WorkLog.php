<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    protected $fillable = [
        'plan_id',
        'task_id',
        'work_session_id',
        'task_title_snapshot',
        'worked_on',
        'actual_minutes',
        'progress_delta_percent',
        'progress_before_percent',
        'progress_after_percent',
        'remaining_minutes_before',
        'remaining_minutes_after',
        'difficulty',
        'memo',
        'outcome',
    ];


    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'actual_minutes' => 'integer',
            'progress_delta_percent' => 'integer',
            'progress_before_percent' => 'integer',
            'progress_after_percent' => 'integer',
            'remaining_minutes_before' => 'integer',
            'remaining_minutes_after' => 'integer',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function workSession()
    {
        return $this->belongsTo(WorkSession::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
