<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $hidden = ['owner_token'];

    protected $fillable = [
        'user_id',
        'owner_token',
        'public_slug',
        'title',
        'description',
        'category',
        'start_date',
        'deadline',
        'is_public',
        'last_ai_context_exported_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'deadline' => 'date',
            'is_public' => 'boolean',
            'last_ai_context_exported_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function workLogs()
    {
        return $this->hasMany(WorkLog::class);
    }

    public function adjustments()
    {
        return $this->hasMany(PlanAdjustment::class);
    }

    public function availabilityRules()
    {
        return $this->hasMany(PlanAvailabilityRule::class);
    }

    public function availabilityOverrides()
    {
        return $this->hasMany(PlanAvailabilityOverride::class);
    }
}
