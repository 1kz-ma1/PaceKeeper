@php
    $focusMode = request()->routeIs('work_sessions.active');
    $isCoreScreen = request()->routeIs('home') || request()->routeIs('chat.index') || request()->routeIs('navigation.index') || request()->routeIs('public_plans.index');
    $mobileSection = match (true) {
        request()->routeIs('navigation.*') => '今日',
        request()->routeIs('work_sessions.*') => '作業',
        request()->routeIs('chat.*'), request()->routeIs('achievements.*'), request()->routeIs('plans.review_assistant.*') => '計画・実績',
        request()->routeIs('public_plans.*') => '公開計画',
        request()->routeIs('plans.*'), request()->routeIs('tasks.*'), request()->routeIs('my_plans.*') => '計画',
        default => 'ホーム',
    };
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PaceKeeper">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>@yield('title', 'Pace Keeper')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased {{ $focusMode ? 'pace-focus-mode' : '' }}">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 top-0 h-80 w-80 rounded-full bg-sky-500/10 blur-3xl"></div>
        <div class="absolute -right-20 bottom-0 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
    </div>

    @unless ($focusMode)
        <header class="desktop-app-header sticky top-0 z-50 hidden border-b border-slate-800/90 bg-slate-950/90 backdrop-blur-xl md:block">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <a href="{{ route('home') }}" class="group inline-flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-400/30 bg-sky-500/15 text-sm font-black text-sky-200 shadow-lg shadow-sky-950/30">PK</span>
                    <span>
                        <span class="font-heading block text-xl font-bold tracking-tight text-slate-50">Pace Keeper</span>
                        <span class="block text-xs font-medium text-slate-400">Adapt the plan, keep moving.</span>
                    </span>
                </a>

                <nav class="flex flex-wrap items-center gap-1 rounded-2xl border border-slate-800 bg-slate-900/75 p-1 text-sm shadow-lg shadow-slate-950/20">
                    <a href="{{ route('chat.index') }}" class="nav-link whitespace-nowrap {{ request()->routeIs('chat.*') || request()->routeIs('plans.review_assistant.*') || request()->routeIs('achievements.*') ? 'nav-link-active' : '' }}">計画・実績</a>
                    <a href="{{ route('navigation.index') }}" data-navigation-link class="nav-link whitespace-nowrap {{ request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') ? 'nav-link-active' : '' }}">今日やること</a>
                    <a href="{{ route('home') }}" class="nav-link whitespace-nowrap {{ request()->routeIs('home') || request()->routeIs('my_plans.*') || request()->routeIs('plans.show') || request()->routeIs('plans.edit') || request()->routeIs('tasks.*') ? 'nav-link-active' : '' }}">ダッシュボード</a>
                    <a href="{{ route('public_plans.index') }}" class="nav-link whitespace-nowrap {{ request()->routeIs('public_plans.*') ? 'nav-link-active' : '' }}">公開計画</a>
                </nav>

                <div class="hidden items-center gap-2 lg:flex">
                    @auth
                        <span class="max-w-36 truncate text-xs font-semibold text-slate-400">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('auth.logout') }}" data-clear-offline-state>
                            @csrf
                            <button type="submit" class="btn-secondary px-3 py-2 text-xs">ログアウト</button>
                        </form>
                    @else
                        <a href="{{ route('auth.login.form') }}" class="btn-secondary px-3 py-2 text-xs">ログイン</a>
                        <a href="{{ route('auth.register.form') }}" class="btn-primary px-3 py-2 text-xs">データを保護</a>
                    @endauth
                </div>
            </div>
        </header>

        <header class="mobile-app-header md:hidden">
            <div class="mobile-app-header-inner">
                @unless ($isCoreScreen)
                    <button type="button" class="mobile-back-button" data-mobile-back aria-label="前の画面に戻る">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                @else
                    <a href="{{ route('home') }}" class="mobile-brand-mark" aria-label="PaceKeeper ホーム">PK</a>
                @endunless
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-300">PaceKeeper</p>
                    <p class="truncate text-sm font-bold text-slate-50">{{ $mobileSection }}</p>
                </div>
                @auth
                    <a href="{{ route('auth.account') }}" class="account-state-dot is-protected" title="アカウント保護済み" aria-label="アカウント設定"></a>
                @else
                    <a href="{{ route('auth.register.form') }}" class="account-state-dot" title="Guest利用中。データを保護できます" aria-label="Guest利用中。データを保護できます"></a>
                @endauth
            </div>
        </header>
    @endunless

    <main class="app-main mx-auto min-h-[calc(100vh-120px)] max-w-7xl px-4 py-5 sm:px-5 md:px-6 md:py-10 {{ $focusMode ? 'focus-main' : '' }}">
        @if (session('status'))
            <div class="assistant-notice assistant-notice-info mb-6" data-auto-toast>{{ session('status') }}</div>
        @endif

        @guest
            @if (request()->routeIs('home') || request()->routeIs('plans.*') || request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') || request()->routeIs('my_plans.*'))
                <div class="guest-protection-banner mb-4">
                    <span><strong>Guest利用中</strong> — このブラウザのCookieが消えると計画を復元できません。</span>
                    <a href="{{ route('auth.register.form') }}">データを保護</a>
                </div>
            @endif
        @endguest

        <div class="sync-status-chip" data-sync-status aria-live="polite">
            <span class="sync-status-dot"></span><span data-sync-status-label>オンライン</span>
        </div>

        @yield('content')
    </main>

    @unless ($focusMode)
        <footer class="mt-12 hidden border-t border-slate-800 bg-slate-950/70 md:block">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-6 py-8 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <p>Pace Keeper - 実績に合わせて計画を育て直す進捗管理ツール</p>
                <p class="text-xs">Built with Laravel</p>
            </div>
        </footer>

        <div class="md:hidden">
            @include('layouts.partials.mobile-nav')
        </div>
    @endunless

    @yield('offline_snapshot')

    <div class="route-loading-overlay" data-route-loading aria-hidden="true">
        <div class="route-loading-card">
            <span class="route-loading-spinner" aria-hidden="true"></span>
            <span>読み込み中…</span>
        </div>
    </div>
</body>
</html>
