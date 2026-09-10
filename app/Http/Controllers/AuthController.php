<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GuestPlanClaimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, GuestPlanClaimService $claimService)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        $request->session()->regenerate();
        $claimed = $claimService->claim($request, $request->user());

        return redirect()->intended(route('home'))->with(
            'status',
            $claimed > 0
                ? "ログインしました。{$claimed}件のGuest計画もこのアカウントに引き継ぎました。"
                : 'ログインしました。'
        );
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request, GuestPlanClaimService $claimService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($validated);
        Auth::login($user, true);
        $request->session()->regenerate();
        $claimed = $claimService->claim($request, $user);

        return redirect()->route('home')->with(
            'status',
            $claimed > 0
                ? "アカウントを作成し、{$claimed}件のGuest計画を保護しました。"
                : 'アカウントを作成しました。これから作る計画はこのアカウントに保存されます。'
        );
    }

    public function account(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('auth.login.form');
        }

        return view('auth.account', ['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'ログアウトしました。保護済みの計画は、再ログインすると表示されます。');
    }
}
