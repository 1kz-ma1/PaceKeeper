<?php

namespace App\Enums;

enum UserBehaviorState: string
{
    case Normal = 'normal';
    case Undecided = 'undecided';
    case LowReadiness = 'low_readiness';
    case Overloaded = 'overloaded';
    case Focused = 'focused';

    public function label(): string
    {
        return match ($this) {
            self::Normal => '普段に近いリズム',
            self::Undecided => '候補を絞ると始めやすい状態',
            self::LowReadiness => '短い作業から始めやすい状態',
            self::Overloaded => '選択肢を減らしたい状態',
            self::Focused => '作業を続けやすい状態',
        };
    }
}
