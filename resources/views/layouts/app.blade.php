<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pace Keeper')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 top-0 h-80 w-80 rounded-full bg-sky-500/10 blur-3xl"></div>
        <div class="absolute -right-20 bottom-0 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
    </div>

    <header class="sticky top-0 z-50 border-b border-slate-800/90 bg-slate-950/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-4 md:flex-row md:items-center md:justify-between md:px-6">
            <a href="{{ route('home') }}" class="group inline-flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-400/30 bg-sky-500/15 text-sm font-black text-sky-200 shadow-lg shadow-sky-950/30">
                    PK
                </span>

                <span>
                    <span class="font-heading block text-xl font-bold tracking-tight text-slate-50">Pace Keeper</span>
                    <span class="block text-xs font-medium text-slate-400">Adapt the plan, keep moving.</span>
                </span>
            </a>

            <nav class="grid grid-cols-4 gap-1 rounded-2xl border border-slate-800 bg-slate-900/75 p-1 text-center text-xs shadow-lg shadow-slate-950/20 sm:flex sm:flex-wrap sm:items-center sm:gap-1 sm:text-sm">
                <a href="{{ route('chat.index') }}"
                   class="nav-link {{ request()->routeIs('chat.*') || request()->routeIs('plans.review_assistant.*') || request()->routeIs('achievements.*') ? 'nav-link-active' : '' }}">
                    計画・実績
                </a>

                <a href="{{ route('navigation.index') }}"
                   data-navigation-link
                   class="nav-link {{ request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') ? 'nav-link-active' : '' }}">
                    今日やること
                </a>

                <a href="{{ route('home') }}"
                   class="nav-link {{ request()->routeIs('home') || request()->routeIs('my_plans.*') || request()->routeIs('plans.show') || request()->routeIs('plans.edit') || request()->routeIs('tasks.*') ? 'nav-link-active' : '' }}">
                    ダッシュボード
                </a>

                <a href="{{ route('public_plans.index') }}"
                   class="nav-link {{ request()->routeIs('public_plans.*') ? 'nav-link-active' : '' }}">
                    公開計画
                </a>
            </nav>
        </div>
    </header>

    <main class="mx-auto min-h-[calc(100vh-180px)] max-w-7xl px-5 py-7 md:px-6 md:py-10">
        @if (session('status'))
            <div class="assistant-notice assistant-notice-info mb-6">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-800 bg-slate-950/70">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-6 py-8 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <p>Pace Keeper - 実績に合わせて計画を育て直す進捗管理ツール</p>
            <p class="text-xs">Built with Laravel</p>
        </div>
    </footer>
</body>
</html>
