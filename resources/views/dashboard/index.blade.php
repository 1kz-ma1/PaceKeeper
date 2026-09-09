@extends('layouts.app')

@section('title', 'ダッシュボード | Pace Keeper')

@section('content')
    @php
        $state = $dashboard['state'];
        $baseline = $dashboard['baseline'];
        $recommendation = $dashboard['recommendation'];
        $activeSession = $dashboard['active_work_session'];
        $todayRemaining = max(0, $dashboard['total_daily_required_minutes'] - $dashboard['today_minutes']);
        $uiMode = $dashboard['ui_mode'] ?? 'balanced';
        $guidedMode = $uiMode === 'guided';
    @endphp

    <div id="behaviorDashboard" class="space-y-7" data-event-url="{{ route('behavior_events.store') }}" data-navigation-url="{{ route('navigation.index') }}" data-work-started="{{ $activeSession ? 1 : 0 }}">
        <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-semibold text-sky-400">Today</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-100 font-heading">次の行動を決める</h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300">{{ $guidedMode ? 'まずは候補を1件に絞って、始めることを優先します。' : '今の状況から、始めやすく価値の高い次の1件を先に表示します。' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('chat.start', 'review') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">計画を更新</button>
                </form>
                <a href="{{ route('navigation.index') }}" data-navigation-link class="btn-primary">今日のおすすめ</a>
            </div>
        </header>

        @if (session('success'))
            <div class="assistant-notice assistant-notice-success">{{ session('success') }}</div>
        @endif
        @if (session('status'))
            <div class="assistant-notice assistant-notice-info">{{ session('status') }}</div>
        @endif

        @if (($dashboard['pending_plan_updates'] ?? collect())->isNotEmpty())
            <section class="rounded-2xl border border-amber-400/25 bg-amber-500/10 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-bold text-amber-200">計画へ未反映の作業結果が{{ $dashboard['pending_plan_updates']->count() }}件あります</p>
                        <p class="mt-1 text-sm text-slate-400">作業時間は保存済みです。余裕のあるときにAIと内容を整理できます。</p>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach ($dashboard['pending_plan_updates'] as $pendingSession)
                        @if ($pendingSession->plan)
                            <a href="{{ route('plans.review_assistant.show', ['plan' => $pendingSession->plan, 'work_session_id' => $pendingSession->id]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-amber-300/15 bg-slate-950/20 px-4 py-3 hover:bg-slate-900/40">
                                <span class="min-w-0"><span class="block truncate font-semibold text-slate-100">{{ $pendingSession->task?->title ?? $pendingSession->plan->title }}</span><span class="mt-1 block text-xs text-slate-400">{{ max(1, (int) ceil(($pendingSession->actual_seconds ?? 0) / 60)) }}分・{{ $pendingSession->ended_at?->format('m/d H:i') }}</span></span>
                                <span class="text-sm font-semibold text-amber-200">反映する →</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if ($activeSession)
            <section class="rounded-2xl border {{ $activeSession->status === 'paused' ? 'border-amber-400/30 bg-amber-500/10' : 'border-emerald-400/30 bg-emerald-500/10' }} p-5">
                <p class="text-xs font-bold uppercase tracking-wider {{ $activeSession->status === 'paused' ? 'text-amber-300' : 'text-emerald-300' }}">
                    {{ $activeSession->status === 'paused' ? '一時停止中' : '作業中' }}
                </p>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-50">{{ $activeSession->task?->title ?? '作業中のTask' }}</h2>
                        <p class="mt-1 text-sm text-slate-300">
                            {{ $activeSession->plan?->title }}・実作業
                            <span
                                data-work-timer
                                data-started-at="{{ $activeSession->started_at?->toIso8601String() }}"
                                data-paused-at="{{ $activeSession->paused_at?->toIso8601String() }}"
                                data-paused-seconds="{{ $activeSession->paused_seconds ?? 0 }}"
                                data-session-status="{{ $activeSession->status }}"
                            >計測中</span>
                        </p>
                    </div>
                    <a href="{{ route('work_sessions.active', $activeSession) }}" class="btn-primary">作業画面へ戻る</a>
                </div>
            </section>
        @endif

        <nav class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/75 p-1" aria-label="ダッシュボード表示">
            <div class="flex min-w-max gap-1" role="tablist">
                <button type="button" class="dashboard-tab nav-link nav-link-active" data-dashboard-tab="overall" role="tab" aria-selected="true">全体</button>
                @foreach ($dashboard['plan_tabs'] as $item)
                    <button type="button" class="dashboard-tab nav-link" data-dashboard-tab="plan-{{ $item['plan']->id }}" data-plan-id="{{ $item['plan']->id }}" role="tab" aria-selected="false">{{ $item['plan']->title }}</button>
                @endforeach
            </div>
        </nav>

        <section data-dashboard-panel="overall" class="space-y-6">
            @if ($recommendation)
                <section class="page-card p-6 ring-1 ring-sky-200">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-sky-600">今から進めるなら</p>
                            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $recommendation->task->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $recommendation->plan->title }}・約{{ $recommendation->recommendedMinutes }}分</p>
                            @if ($recommendation->task->next_action_note)
                                <p class="mt-3 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-900">前回メモ：{{ $recommendation->task->next_action_note }}</p>
                            @endif
                            @if ($recommendation->reasons)
                                <ul class="mt-4 space-y-1 text-sm text-slate-600">
                                    @foreach ($recommendation->reasons as $reason)
                                        <li>・{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <form method="POST" action="{{ route('work_sessions.start') }}" data-work-start-form>
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $recommendation->task->id }}">
                                <input type="hidden" name="intended_minutes" value="{{ $recommendation->recommendedMinutes }}">
                                <input type="hidden" name="source" value="dashboard">
                                <button class="btn-primary">この作業を始める</button>
                            </form>
                            <form method="POST" action="{{ route('recommendations.alternative') }}">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $recommendation->task->id }}">
                                <button class="btn-secondary">別の候補</button>
                            </form>
                        </div>
                    </div>
                </section>
            @else
                <section class="page-card p-6">
                    <h2 class="text-xl font-bold text-slate-900">今すぐ始める候補はありません</h2>
                    <p class="mt-2 text-sm text-slate-600">未完了Taskがない場合はPlan詳細から追加してください。</p>
                </section>
            @endif

            <div data-idle-nudge class="hidden rounded-2xl border border-sky-300/30 bg-sky-500/10 p-5 text-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-bold">迷ったら、Pace Keeperのおすすめからそのまま始められます。</p>
                        <p class="mt-1 text-sm text-slate-300">まず1件だけ提示し、気に入らなければ別のTaskへ切り替えられます。</p>
                    </div>
                    <a href="{{ route('navigation.index') }}" data-navigation-link class="btn-primary">おすすめを見る</a>
                </div>
            </div>

            @if ($guidedMode)
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 px-4 py-3 text-sm text-slate-300">
                    今日 {{ $dashboard['today_minutes'] }}分実績・目安まであと {{ $todayRemaining }}分
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="info-card p-5">
                        <p class="text-sm text-slate-500">今日あと必要</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $todayRemaining }}分</p>
                        <p class="mt-1 text-xs text-slate-500">実績 {{ $dashboard['today_minutes'] }}分 / 目安 {{ $dashboard['total_daily_required_minutes'] }}分</p>
                    </div>
                    <div class="info-card p-5">
                        <p class="text-sm text-slate-500">注意が必要なPlan</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $dashboard['attention_plans']->count() }}件</p>
                        <p class="mt-1 text-xs text-slate-500">遅れ・期限の状況から抽出</p>
                    </div>
                    <div class="info-card p-5">
                        <p class="text-sm text-slate-500">作業リズム</p>
                        @if ($dashboard['analysis_ready'])
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $state->state->label() }}</p>
                            <p class="mt-1 text-xs text-slate-500">本人の過去データとの比較を含みます</p>
                        @else
                            <p class="mt-2 text-xl font-bold text-slate-900">学習中</p>
                            <p class="mt-1 text-xs text-slate-500">あと数回の作業で傾向を表示します</p>
                        @endif
                    </div>
                </div>
            @endif

            @if (! empty($dashboard['process_highlights']))
                <section class="page-card p-5">
                    <p class="text-sm font-semibold text-emerald-700">今日の積み上げ</p>
                    <div class="mt-3 space-y-2 text-sm leading-6 text-slate-700">
                        @foreach ($dashboard['process_highlights'] as $highlight)
                            <p>・{{ $highlight }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! $guidedMode && $dashboard['attention_plans']->isNotEmpty())
                <section class="page-card p-6">
                    <h2 class="text-lg font-bold text-slate-900">要確認</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($dashboard['attention_plans'] as $item)
                            <button type="button" class="flex w-full items-center justify-between gap-4 rounded-xl bg-slate-50 p-4 text-left hover:bg-slate-100" data-open-dashboard-tab="plan-{{ $item['plan']->id }}">
                                <span>
                                    <span class="block font-bold text-slate-900">{{ $item['plan']->title }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ $item['progress']['status'] }}・今日の目安 {{ $item['progress']['daily_required_minutes'] }}分</span>
                                </span>
                                <span class="text-sm font-semibold text-sky-600">見る</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($guidedMode)
                <details class="page-card p-5">
                    <summary class="cursor-pointer font-semibold text-slate-900">今日の状況</summary>
                    <p class="mt-3 text-sm text-slate-600">{{ $state->state->label() }}。今は候補を絞るため、詳細な分析表示を控えています。</p>
                </details>
            @else
            <section class="page-card p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">行動の傾向</h2>
                        <p class="mt-1 text-sm text-slate-500">結果だけでなく、始め方や続け方も振り返ります。</p>
                    </div>
                    <span class="badge badge-slate">連続 {{ $dashboard['streak_days'] }}日</span>
                </div>

                @if (! $dashboard['analysis_ready'])
                    <div class="mt-5 rounded-xl bg-slate-50 p-5">
                        <p class="font-bold text-slate-900">あなたの作業リズムを学習中です</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">現在の標本 {{ $baseline->sampleCount }}件・活動日 {{ $dashboard['active_days'] }}日。十分なデータが集まるまでは精密な数値を表示しません。</p>
                    </div>
                @else
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">始めやすさ</p><p class="mt-1 font-bold text-slate-900">{{ $state->actionReadiness >= 65 ? '高め' : ($state->actionReadiness >= 45 ? '普段どおり' : '低め') }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">選びやすさ</p><p class="mt-1 font-bold text-slate-900">{{ $state->decisionLoad <= 35 ? '選びやすい' : ($state->decisionLoad <= 60 ? '普段どおり' : '迷いやすい') }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">集中継続</p><p class="mt-1 font-bold text-slate-900">{{ $state->focusContinuity >= 65 ? '続きやすい' : ($state->focusContinuity >= 40 ? '普段どおり' : '短め') }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">取り組みの安定</p><p class="mt-1 font-bold text-slate-900">{{ $state->consistency >= 65 ? '安定' : ($state->consistency >= 40 ? '普段どおり' : '波あり') }}</p></div>
                    </div>
                    @if (! $dashboard['trend_ready'])
                        <p class="mt-4 text-sm text-slate-500">推移グラフは、複数日のデータが蓄積すると表示できるようになります。</p>
                    @endif
                @endif
            </section>
            @endif
        </section>

        @foreach ($dashboard['plan_tabs'] as $item)
            @php
                $planRecommendation = $item['recommendation'];
                $previousSession = $item['previous_session'];
            @endphp
            <section data-dashboard-panel="plan-{{ $item['plan']->id }}" class="hidden space-y-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="info-card p-5"><p class="text-sm text-slate-500">状態</p><p class="mt-2 text-xl font-bold text-slate-900">{{ $item['progress']['status'] }}</p></div>
                    <div class="info-card p-5"><p class="text-sm text-slate-500">進捗</p><p class="mt-2 text-xl font-bold text-slate-900">{{ $item['progress']['weighted_progress_percent'] }}% <span class="text-sm font-normal text-slate-500">/ 期待 {{ $item['progress']['expected_progress_percent'] }}%</span></p></div>
                    <div class="info-card p-5"><p class="text-sm text-slate-500">今日必要</p><p class="mt-2 text-xl font-bold text-slate-900">{{ $item['progress']['daily_required_minutes'] }}分</p><p class="text-xs text-slate-500">実績 {{ $item['today_minutes'] }}分</p></div>
                    <div class="info-card p-5"><p class="text-sm text-slate-500">期限・残り</p><p class="mt-2 font-bold text-slate-900">{{ $item['plan']->deadline->format('Y-m-d') }}</p><p class="text-xs text-slate-500">残り {{ round($item['progress']['remaining_minutes'] / 60, 1) }}時間</p></div>
                </div>

                @if ($planRecommendation)
                    <section class="page-card p-6 ring-1 ring-sky-200">
                        <p class="text-sm font-semibold text-sky-600">このPlanを今進めるなら</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $planRecommendation->task->title }}</h2>
                        <p class="mt-2 text-sm text-slate-500">約{{ $planRecommendation->recommendedMinutes }}分・開始ハードル {{ $planRecommendation->task->activation_cost ?? 3 }}/5</p>
                        @if ($planRecommendation->task->next_action_note)
                            <p class="mt-3 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-900">次回ここから：{{ $planRecommendation->task->next_action_note }}</p>
                        @endif
                        <ul class="mt-3 text-sm text-slate-600">@foreach ($planRecommendation->reasons as $reason)<li>・{{ $reason }}</li>@endforeach</ul>
                        <form method="POST" action="{{ route('work_sessions.start') }}" class="mt-5" data-work-start-form>
                            @csrf
                            <input type="hidden" name="task_id" value="{{ $planRecommendation->task->id }}">
                            <input type="hidden" name="intended_minutes" value="{{ $planRecommendation->recommendedMinutes }}">
                            <input type="hidden" name="source" value="dashboard">
                            <button type="submit" class="btn-primary">この作業を始める</button>
                        </form>
                    </section>
                @endif

                @if ($previousSession?->task)
                    <section class="page-card p-5" data-task-view data-task-id="{{ $previousSession->task->id }}" data-plan-id="{{ $item['plan']->id }}">
                        <p class="text-sm text-slate-500">前回の続き</p>
                        <h3 class="mt-1 font-bold text-slate-900">{{ $previousSession->task->title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $previousSession->ended_at?->diffForHumans() }}・実作業 {{ max(1, (int) ceil(($previousSession->actual_seconds ?? 0) / 60)) }}分</p>
                        @if ($previousSession->task->next_action_note)
                            <p class="mt-2 text-sm text-sky-700">次回ここから：{{ $previousSession->task->next_action_note }}</p>
                        @endif
                        @if (! in_array($previousSession->task->status, ['done', 'cancelled'], true))
                            <form method="POST" action="{{ route('work_sessions.start') }}" class="mt-4" data-work-start-form>
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $previousSession->task->id }}">
                                <input type="hidden" name="source" value="dashboard">
                                <button class="btn-secondary">続きをやる</button>
                            </form>
                        @endif
                    </section>
                @endif

                <section class="page-card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-slate-900">最近の作業</h2>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('plans.review_assistant.show', $item['plan']) }}" class="btn-primary px-3 py-2 text-sm">計画を更新</a>
                            <a href="{{ route('plans.show', $item['plan']) }}" class="btn-secondary px-3 py-2 text-sm">Task一覧・Plan詳細</a>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        @forelse ($item['recent_logs'] as $log)
                            <p>{{ $log->worked_on?->format('m/d') }}・{{ $log->task?->title ?? $log->task_title_snapshot ?? 'Plan全体' }}・{{ $log->actual_minutes }}分</p>
                        @empty
                            <p>最近の作業記録はありません。</p>
                        @endforelse
                    </div>
                </section>
            </section>
        @endforeach

        @if (config('features.pacekeeper_ai'))
            <section class="page-card p-6">
                <p class="text-sm font-semibold text-violet-600">PaceKeeper AI</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">今の状況をもとに相談する</h2>
                <p class="mt-2 text-sm text-slate-600">将来のアプリ内伴走AI用の差し込み領域です。</p>
            </section>
        @endif

        <div class="text-right"><a href="{{ route('dashboard.tools') }}" class="text-sm text-slate-400 hover:text-slate-200">AI JSON・管理ツールを開く</a></div>
    </div>
@endsection
