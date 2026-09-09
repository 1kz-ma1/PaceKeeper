<?php

namespace App\Services;

use App\Data\UserStateData;
use App\Enums\UserBehaviorState;

class NavigationFlowService
{
    public function intentOptions(UserStateData $state): array
    {
        if (in_array($state->state, [UserBehaviorState::LowReadiness, UserBehaviorState::Overloaded], true)) {
            return [
                'short' => '短時間だけ進めたい',
                'decide' => '候補を1つに絞ってほしい',
                'preferred' => '好きなPlanを進めたい',
            ];
        }

        $options = [
            'decide' => '何をやるか決めたい',
            'short' => '短時間だけ進めたい',
            'recover' => '遅れを取り戻したい',
            'preferred' => '好きなPlanを進めたい',
        ];

        if ($state->state === UserBehaviorState::Focused) {
            $options['continue'] = '今の流れで続けたい';
        }

        return $options;
    }

    public function timeOptions(UserStateData $state): array
    {
        $options = [15 => '15分', 30 => '30分', 60 => '1時間'];

        if (! in_array($state->state, [UserBehaviorState::LowReadiness, UserBehaviorState::Overloaded], true)) {
            $options[0] = '時間は気にしない';
        }

        return $options;
    }
}
