@extends('layouts.app')

@section('title', '今日やること | Pace Keeper')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <header class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-sky-400">Today</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-100">今日やること</h1>
                <p class="mt-2 text-sm text-slate-400">
                    Pace Keeperが今の進捗・Task特性・これまでの選択傾向から、まず1つに絞りました。
                </p>
            </div>
            @if (($draft['step'] ?? 'recommendation') !== 'recommendation')
                <form method="POST" action="{{ route('navigation.reset') }}">
                    @csrf
                    <button class="btn-secondary">おすすめに戻る</button>
                </form>
            @endif
        </header>

        @if ($scopePlan)
            <div class="rounded-2xl border border-sky-400/20 bg-sky-500/10 px-4 py-3 text-sm text-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p><span class="font-semibold text-sky-300">{{ $scopePlan->title }}</span> の中からおすすめしています。</p>
                    <a href="{{ route('navigation.index', ['all' => 1]) }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">全Planから選ぶ</a>
                </div>
            </div>
        @endif

        <main class="assistant-chat-shell">
            @if (($draft['step'] ?? 'recommendation') === 'intent')
                <div class="assistant-message-row assistant-message-left">
                    <div class="assistant-avatar">PK</div>
                    <div class="assistant-bubble assistant-bubble-support assistant-wide-bubble">
                        <p class="text-sm font-semibold text-sky-300">条件を変える</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-100">今日はどう進めたい？</h2>
                        <p class="mt-2 text-sm text-slate-400">必要なときだけ条件を指定してください。通常はおすすめをそのまま開始できます。</p>
                        <div class="chat-action-grid mt-5">
                            @foreach ($intentOptions as $value => $label)
                                <form method="POST" action="{{ route('navigation.intent') }}">
                                    @csrf
                                    <input type="hidden" name="intent" value="{{ $value }}">
                                    <button class="chat-action-card w-full text-left"><span class="font-bold text-slate-100">{{ $label }}</span></button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif (($draft['step'] ?? null) === 'time')
                <div class="assistant-message-row assistant-message-left">
                    <div class="assistant-avatar">PK</div>
                    <div class="assistant-bubble assistant-bubble-support assistant-wide-bubble">
                        <h2 class="text-xl font-bold text-slate-100">どれくらい時間がありますか？</h2>
                        <form method="POST" action="{{ route('navigation.time') }}" class="mt-5 space-y-5">
                            @csrf
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($timeOptions as $value => $label)
                                    <label class="chat-choice-card">
                                        <input type="radio" name="minutes" value="{{ $value }}" required>
                                        <span class="font-bold text-slate-100">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if (($draft['intent'] ?? null) === 'preferred')
                                <label>
                                    <span class="form-label">進めたいPlan</span>
                                    <select name="preferred_plan_id" class="form-control" required>
                                        <option value="">選択</option>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->title }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                            <button class="btn-primary">この条件でおすすめを見る</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="assistant-message-row assistant-message-left">
                    <div class="assistant-avatar">PK</div>
                    <div class="assistant-bubble assistant-bubble-support assistant-wide-bubble">
                        @if ($recommendation)
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-sky-300">今日のおすすめ</p>
                                    <h2 class="mt-2 text-2xl font-bold text-slate-100">{{ $recommendation->task->title }}</h2>
                                    <p class="mt-1 text-slate-400">{{ $recommendation->plan->title }}</p>
                                </div>
                                <div class="rounded-2xl border border-sky-400/20 bg-slate-950/30 px-4 py-3 text-right">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">目安</p>
                                    <p class="mt-1 text-2xl font-black tabular-nums text-slate-100">{{ $recommendation->recommendedMinutes }}分</p>
                                </div>
                            </div>

                            @if ($recommendation->task->next_action_note)
                                <p class="mt-4 rounded-xl border border-sky-400/15 bg-sky-500/10 px-4 py-3 text-sm text-slate-200">
                                    前回メモ：{{ $recommendation->task->next_action_note }}
                                </p>
                            @endif

                            @if ($recommendation->reasons)
                                <ul class="mt-4 space-y-2 text-sm text-slate-300">
                                    @foreach ($recommendation->reasons as $reason)
                                        <li>・{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <section class="mt-6 rounded-3xl border border-emerald-400/15 bg-emerald-500/5 px-5 py-6 text-center">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">Ready</p>
                                <p class="mt-3 text-5xl font-black tabular-nums text-slate-100">00:00</p>
                                <p class="mt-2 text-sm text-slate-400">開始した瞬間から自動で計測します。まず{{ $recommendation->recommendedMinutes }}分を目安に進めます。</p>
                                <form method="POST" action="{{ route('work_sessions.start') }}" data-work-start-form class="mt-5">
                                    @csrf
                                    <input type="hidden" name="task_id" value="{{ $recommendation->task->id }}">
                                    <input type="hidden" name="intended_minutes" value="{{ $recommendation->recommendedMinutes }}">
                                    <input type="hidden" name="source" value="navigation">
                                    <button class="btn-primary w-full justify-center text-base sm:w-auto sm:min-w-56">このまま開始</button>
                                </form>
                            </section>

                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                <form method="POST" action="{{ route('navigation.alternative') }}">
                                    @csrf
                                    <input type="hidden" name="task_id" value="{{ $recommendation->task->id }}">
                                    <button class="btn-secondary">別のTaskにする</button>
                                </form>
                                <a href="{{ route('navigation.index', ['configure' => 1]) }}" class="text-sm font-semibold text-sky-300 hover:text-sky-200">条件を変えて選ぶ</a>
                            </div>

                            <p class="mt-4 text-xs leading-6 text-slate-500">
                                別のTaskを選んだことや、このまま開始したことも次回のおすすめ改善に使われます。
                            </p>
                        @else
                            <h2 class="text-xl font-bold text-slate-100">今すぐ始められるTaskが見つかりませんでした</h2>
                            <p class="mt-2 text-slate-400">別候補を見切った場合はおすすめを戻すか、条件を変えて探せます。</p>
                            <div class="mt-5 flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('navigation.reset') }}">
                                    @csrf
                                    <button class="btn-primary">最初のおすすめに戻る</button>
                                </form>
                                <a href="{{ route('navigation.index', ['configure' => 1]) }}" class="btn-secondary">条件を変える</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </main>
    </div>
@endsection
