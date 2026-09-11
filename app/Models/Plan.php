<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public const ACCENT_KEYS = ['sky', 'emerald', 'violet', 'amber', 'rose', 'cyan'];

    public const ROADMAP_WORLDS = ['default', 'study', 'sweet', 'halloween', 'space', 'forest'];

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
        'visual_icon',
        'accent_key',
        'roadmap_world',
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

    public function displayIcon(): string
    {
        if (filled($this->visual_icon)) {
            return mb_substr((string) $this->visual_icon, 0, 4);
        }

        return match ($this->category) {
            '資格学習' => '📘',
            'ゲーム開発' => '🎮',
            '個人開発' => '💻',
            '制作活動' => '🛠️',
            default => '🧭',
        };
    }

    public function accentKey(): string
    {
        $accent = (string) ($this->accent_key ?: 'sky');

        return in_array($accent, self::ACCENT_KEYS, true) ? $accent : 'sky';
    }

    public function roadmapWorld(): string
    {
        $world = (string) ($this->roadmap_world ?: 'default');

        return in_array($world, self::ROADMAP_WORLDS, true) ? $world : 'default';
    }
}
