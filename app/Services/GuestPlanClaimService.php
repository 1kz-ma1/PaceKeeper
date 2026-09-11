<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class GuestPlanClaimService
{
    public function claim(Request $request, User $user): int
    {
        $claimed = 0;

        foreach ($request->cookies->all() as $name => $token) {
            if (! preg_match('/^pace_keeper_owner_token_(\d+)$/', (string) $name, $matches)) {
                continue;
            }

            $plan = Plan::find((int) $matches[1]);
            if (! $plan || ! is_string($token) || $token === '' || ! hash_equals($plan->owner_token, $token)) {
                continue;
            }

            if ($plan->user_id === null) {
                $plan->forceFill(['user_id' => $user->id])->save();
                $claimed++;
            }

            if ((int) $plan->user_id === (int) $user->id) {
                // Once a plan is account-owned, the legacy Guest ownership
                // cookie is no longer useful. Remove it so the browser does
                // not keep stale ownership material indefinitely.
                cookie()->queue(cookie()->forget((string) $name));
            }
        }

        return $claimed;
    }
}
