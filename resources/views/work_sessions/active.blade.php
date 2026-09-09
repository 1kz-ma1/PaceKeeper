@extends('layouts.app')

@section('title', '作業中 | Pace Keeper')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        @if (session('status'))
            <div class="assistant-notice assistant-notice-info">{{ session('status') }}</div>
        @endif

        <section class="page-card p-8 text-center">
            <p class="text-sm font-semibold {{ $workSession->status === 'paused' ? 'text-amber-500' : 'text-emerald-600' }}">
                {{ $workSession->status === 'paused' ? '一時停止中' : '作業中' }}
            </p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $workSession->task?->title ?? 'Taskは削除されました' }}</h1>
            <p class="mt-2 text-slate-500">{{ $workSession->plan?->title }}{{ $workSession->intended_minutes ? '・目安 ' . $workSession->intended_minutes . '分' : '' }}</p>
            <p
                class="mt-8 text-5xl font-black tabular-nums text-slate-900"
                data-work-timer
                data-started-at="{{ $workSession->started_at?->toIso8601String() }}"
                data-paused-at="{{ $workSession->paused_at?->toIso8601String() }}"
                data-paused-seconds="{{ $workSession->paused_seconds ?? 0 }}"
                data-session-status="{{ $workSession->status }}"
                data-initial-active-seconds="{{ $activeSeconds }}"
            >00:00</p>
            <p class="mt-2 text-xs text-slate-400">一時停止中の時間は実作業時間に含まれません。</p>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                @if ($workSession->status === 'paused')
                    <form method="POST" action="{{ route('work_sessions.resume', $workSession) }}">
                        @csrf
                        <button class="btn-primary w-full justify-center">再開する</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('work_sessions.pause', $workSession) }}">
                        @csrf
                        <button class="btn-secondary w-full justify-center">一時停止</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('work_sessions.complete', $workSession) }}">
                    @csrf
                    <button class="btn-primary w-full justify-center">記録して終了</button>
                </form>
            </div>

            <form method="POST" action="{{ route('work_sessions.interrupt', $workSession) }}" class="mt-3">
                @csrf
                <button class="btn-secondary w-full justify-center">中断して終了</button>
            </form>
        </section>
        <p class="text-center text-sm text-slate-400">「記録して終了」で作業時間を確定し、その後は普段使っているAIから計画へ反映できます。</p>
    </div>
@endsection
