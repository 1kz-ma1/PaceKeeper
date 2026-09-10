<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BehaviorIdentityService
{
    public const COOKIE_NAME = 'pace_keeper_actor_token';

    private const SESSION_KEY = 'pace_keeper.actor_token';

    public function resolve(Request $request): string
    {
        $token = $request->session()->get(self::SESSION_KEY)
            ?? $request->cookie(self::COOKIE_NAME);

        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            $token = Str::random(64);
        }

        $request->session()->put(self::SESSION_KEY, $token);

        if ($request->cookie(self::COOKIE_NAME) !== $token) {
            cookie()->queue(self::COOKIE_NAME, $token, 60 * 24 * 365 * 2, '/', null, app()->environment('production') || $request->isSecure(), true, false, 'lax');
        }

        return $token;
    }
}
