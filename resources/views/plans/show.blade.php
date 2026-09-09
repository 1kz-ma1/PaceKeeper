@extends('layouts.app')

@section('title', $plan->title . ' | Pace Keeper')

@section('content')
    @php
        $status = $progress['status'] ?? '予定通り';

        $progressColorClass = match ($status) {
            '順調' => 'progress-green',
            '予定通り' => 'progress-blue',
            '遅れ気味' => 'progress-amber',
            '期限切れ' => 'progress-red',
            '作業時間不足' => 'progress-red',
            default => 'progress-slate',
        };

        $statusClass = match ($status) {
            '順調' => 'status-green',
            '予定通り' => 'status-blue',
            '遅れ気味' => 'status-amber',
            '期限切れ' => 'status-red',
            '作業時間不足' => 'status-red',
            default => 'status-slate',
        };
    @endphp

    @if (session('success'))
        <div class="assistant-notice assistant-notice-success mb-6">{{ session('success') }}</div>
    @endif

    @if (session('status'))
        <div class="assistant-notice assistant-notice-info mb-6">{{ session('status') }}</div>
    @endif

    <section class="mb-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="mb-2 text-sm font-semibold text-slate-500">Plan Detail</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $plan->title }}</h1>
                <p class="mt-3 max-w-3xl leading-7 text-slate-600">
                    {{ $plan->description ?? '説明はまだ設定されていません。' }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="badge badge-slate">{{ $plan->category ?? '未設定' }}</span>
                    <span class="badge {{ $plan->is_public ? 'badge-green' : 'badge-slate' }}">
                        {{ $plan->is_public ? '公開' : '非公開' }}
                    </span>
                </div>
            </div>

            @if ($canEdit ?? false)
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('plans.review_assistant.show', $plan) }}" class="btn-primary">計画を更新</a>
                    <form method="POST" action="{{ route('chat.start', 'ai_context') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn-secondary">AIに現状を共有</button>
                    </form>
                    <a href="{{ route('plans.edit', $plan) }}" class="btn-secondary">計画を編集</a>
                </div>
            @endif
        </div>
    </section>

    @if ($canEdit ?? false)
        <section class="mb-8 adaptive-entry-card">
            <div>
                <p class="text-sm font-semibold text-sky-600">Adaptive workflow</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">計画外の作業も、そのまま記録できます</h2>
                <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">
                    作業ログや新規タスクを手動で計画へ合わせるのではなく、実際に行ったことを入力してください。
                    外部AIの提案を確認してから、ログ追加・進捗更新・タスク追加・タスク中止をまとめて反映できます。
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('plans.review_assistant.show', $plan) }}" class="btn-primary">実績・方針をまとめて更新</a>
                @if ($plan->tasks->isEmpty())
                    <a href="{{ route('plans.ai_task_assistant.show', $plan) }}" class="btn-secondary">AIで初期計画を生成</a>
                @endif
            </div>
        </section>
    @endif

    <section class="mb-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="info-card p-5">
            <p class="text-sm text-slate-500">期間</p>
            <p class="mt-2 font-bold text-slate-900">{{ $plan->start_date->format('Y-m-d') }} 〜 {{ $plan->deadline->format('Y-m-d') }}</p>
        </div>
        <div class="info-card p-5">
            <p class="text-sm text-slate-500">残り日数</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $progress['remaining_days'] }}日</p>
        </div>
        <div class="info-card p-5">
            <p class="text-sm text-slate-500">{{ ($progress['availability_configured'] ?? false) ? '今日の作業目安' : '1日あたり必要時間' }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $progress['daily_required_minutes'] }}分</p>
            @if (($progress['availability_configured'] ?? false))
                <p class="mt-1 text-xs text-slate-500">作業可能 {{ $progress['today_available_minutes'] ?? 0 }}分</p>
            @endif
        </div>
        <div class="info-card p-5">
            <p class="text-sm text-slate-500">状態</p>
            <p class="mt-3"><span class="status-pill {{ $statusClass }}">{{ $status }}</span></p>
        </div>
    </section>

    @if (($progress['availability_configured'] ?? false))
        <section class="mb-8 page-card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-sky-600">Availability</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">作業可能時間</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">毎日同じ量を前提にせず、授業・休日・長期休暇などの現実の制約を進捗計算とおすすめ時間に使います。</p>
                </div>
                @if ($canEdit ?? false)
                    <a href="{{ route('plans.review_assistant.show', $plan) }}" class="btn-secondary">AIと作業可能時間を更新</a>
                @endif
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($plan->availabilityRules->sortBy('day_of_week') as $rule)
                    @php $dayLabels = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土']; @endphp
                    <div class="metric-card"><p class="text-xs text-slate-500">{{ $dayLabels[$rule->day_of_week] ?? $rule->day_of_week }}曜日</p><p class="mt-1 font-bold text-slate-900">{{ $rule->available_minutes }}分{{ $rule->is_optional ? '（任意）' : '' }}</p></div>
                @endforeach
            </div>
            @if ($plan->availabilityOverrides->isNotEmpty())
                <div class="mt-5 border-t border-slate-200 pt-4">
                    <p class="text-sm font-semibold text-slate-700">例外日</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($plan->availabilityOverrides->sortBy('date')->take(12) as $override)
                            <span class="badge badge-slate">{{ $override->date->format('m/d') }} {{ $override->available_minutes }}分{{ $override->note ? '・'.$override->note : '' }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                残り作業 {{ $progress['remaining_minutes'] }}分 / 残り作業可能 {{ $progress['remaining_available_minutes'] }}分
                @if (($progress['required_capacity_ratio'] ?? null) !== null)
                    ・必要負荷率 {{ round($progress['required_capacity_ratio'] * 100) }}%
                @endif
            </div>
        </section>
    @endif

    <section class="mb-8 page-card p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">進捗情報</h2>
                <p class="mt-1 text-sm text-slate-500">
                    中止したタスクは必要作業量と進捗率の計算から除外しています。
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">現在の進捗率</p>
                <p class="text-3xl font-bold text-slate-900">{{ $progress['weighted_progress_percent'] }}%</p>
            </div>
        </div>

        <div class="mb-6 progress-track h-3">
            <div class="progress-bar {{ $progressColorClass }}" style="width: {{ min(100, max(0, $progress['weighted_progress_percent'])) }}%;"></div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
            <div class="metric-card">
                <p class="text-sm text-slate-500">合計想定時間</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ round($progress['total_estimated_minutes'] / 60, 1) }}時間</p>
            </div>
            <div class="metric-card">
                <p class="text-sm text-slate-500">合計実績時間</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ round($progress['total_actual_minutes'] / 60, 1) }}時間</p>
            </div>
            <div class="metric-card">
                <p class="text-sm text-slate-500">期待進捗率</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $progress['expected_progress_percent'] }}%</p>
            </div>
            <div class="metric-card">
                <p class="text-sm text-slate-500">残り時間</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ round($progress['remaining_minutes_by_progress'] / 60, 1) }}時間</p>
            </div>
            <div class="metric-card">
                <p class="text-sm text-slate-500">中止タスク</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $progress['cancelled_task_count'] ?? 0 }}件</p>
            </div>
        </div>
    </section>

    @if ($plan->is_public)
        <section class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-5">
            <h2 class="font-bold text-green-900">公開URL</h2>
            <a href="{{ route('public_plans.show', $plan->public_slug) }}" class="mt-3 inline-flex break-all text-sm font-medium text-green-900 hover:underline">
                {{ route('public_plans.show', $plan->public_slug) }}
            </a>
        </section>
    @endif

    <section class="mb-8 page-card p-6">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">タスク一覧</h2>
                <p class="mt-1 text-sm text-slate-500">追加や進捗・方針の更新は「計画を更新」からまとめて行い、ここでは確認・編集・削除を行います。</p>
            </div>
        </div>

        @if ($plan->tasks->isEmpty())
            <div class="empty-state">
                <p class="font-bold text-slate-900">まだタスクはありません。</p>
                @if ($canEdit ?? false)
                    <form method="POST" action="{{ route('chat.start', 'task') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn-primary">チャットでタスクを追加</button>
                    </form>
                @endif
            </div>
        @else
            <div class="space-y-4">
                @foreach ($plan->tasks as $task)
                    @php
                        $taskProgressColorClass = match ($task->status) {
                            'done' => 'progress-green',
                            'doing' => 'progress-blue',
                            'cancelled' => 'progress-red',
                            default => 'progress-slate',
                        };
                        $taskStatusLabel = match ($task->status) {
                            'todo' => '未着手',
                            'doing' => '進行中',
                            'done' => '完了',
                            'cancelled' => '中止',
                            default => $task->status,
                        };
                        $taskStatusClass = match ($task->status) {
                            'done' => 'status-green',
                            'doing' => 'status-blue',
                            'cancelled' => 'status-red',
                            default => 'status-slate',
                        };
                    @endphp

                    <article class="rounded-xl border border-slate-200 p-4 {{ $task->status === 'cancelled' ? 'opacity-70' : '' }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-slate-900">{{ $task->title }}</h3>
                                    <span class="status-pill {{ $taskStatusClass }}">{{ $taskStatusLabel }}</span>
                                    @if ($task->continuation_of_task_id)
                                        <span class="badge badge-slate">前回作業から切り出し</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $task->description ?? '説明なし' }}</p>
                                @if ($task->prerequisite)
                                    <p class="mt-2 text-xs font-medium text-amber-700">前提：{{ $task->prerequisite->title }}（{{ $task->prerequisite->status === 'done' ? '完了' : '未完了' }}）</p>
                                @endif
                            </div>

                            @if ($canEdit ?? false)
                                <div class="flex flex-wrap gap-2">
                                    @if (! in_array($task->status, ['done', 'cancelled'], true))
                                        <form method="POST" action="{{ route('work_sessions.start') }}">
                                            @csrf
                                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                                            <input type="hidden" name="source" value="plan">
                                            <button type="submit" class="btn-primary px-3 py-2 text-sm">このTaskを始める</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('tasks.edit', $task) }}" class="btn-secondary px-3 py-2 text-sm">編集</a>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-6">
                            <div class="metric-card"><p class="text-xs text-slate-500">総想定時間</p><p class="font-semibold text-slate-900">{{ $task->estimated_minutes }}分</p></div>
                            <div class="metric-card"><p class="text-xs text-slate-500">残り時間</p><p class="font-semibold text-slate-900">{{ $task->remaining_minutes ?? 0 }}分</p></div>
                            <div class="metric-card"><p class="text-xs text-slate-500">進捗率</p><p class="font-semibold text-slate-900">{{ $task->progress_percent }}%</p></div>
                            <div class="metric-card"><p class="text-xs text-slate-500">開始ハードル</p><p class="font-semibold text-slate-900">{{ $task->activation_cost ?? 3 }}/5</p></div>
                            <div class="metric-card"><p class="text-xs text-slate-500">状態</p><p class="font-semibold text-slate-900">{{ $taskStatusLabel }}</p></div>
                            <div class="metric-card"><p class="text-xs text-slate-500">優先度</p><p class="font-semibold text-slate-900">{{ $task->priority }}</p></div>
                        </div>

                        <div class="mt-4 progress-track">
                            <div class="progress-bar {{ $taskProgressColorClass }}" style="width: {{ min(100, max(0, $task->progress_percent)) }}%;"></div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="page-card p-6">
        <h2 class="text-2xl font-bold text-slate-900">Timeline</h2>
        <p class="mt-1 text-sm text-slate-500">作業結果と計画変更を、現在地がどう変わったかと一緒に時系列で残します。</p>

        @if ($timeline->isEmpty())
            <div class="empty-state mt-5">まだ記録はありません。</div>
        @else
            <div class="mt-5 space-y-4 border-l-2 border-slate-200 pl-5">
                @foreach ($timeline as $event)
                    <article class="relative rounded-xl border border-slate-200 p-4">
                        <span class="absolute -left-[1.85rem] top-5 h-3 w-3 rounded-full {{ $event['type'] === 'result' ? 'bg-sky-500' : 'bg-emerald-500' }}"></span>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <span class="badge {{ $event['type'] === 'result' ? 'badge-slate' : 'badge-green' }}">{{ $event['type'] === 'result' ? '結果' : '変更' }}</span>
                                <h3 class="mt-2 font-bold text-slate-900">{{ $event['title'] }}</h3>
                            </div>
                            <p class="text-sm text-slate-500">{{ $event['date_label'] }}</p>
                        </div>

                        @if ($event['summary'])
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $event['summary'] }}</p>
                        @endif

                        @if ($event['type'] === 'result')
                            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                <span class="badge badge-slate">{{ $event['actual_minutes'] }}分</span>
                                @if ($event['progress_after'] !== null)<span class="badge badge-slate">進捗 {{ $event['progress_before'] ?? '—' }}% → {{ $event['progress_after'] }}%</span>@endif
                                @if ($event['remaining_after'] !== null)<span class="badge badge-slate">残り {{ $event['remaining_before'] ?? '—' }}分 → {{ $event['remaining_after'] }}分</span>@endif
                            </div>
                        @else
                            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                <span class="badge badge-slate">{{ $event['operation_count'] }}操作</span>
                                @if (data_get($event, 'metrics_after.weighted_progress_percent') !== null)
                                    <span class="badge badge-slate">計画進捗 {{ data_get($event, 'metrics_before.weighted_progress_percent', '—') }}% → {{ data_get($event, 'metrics_after.weighted_progress_percent') }}%</span>
                                    <span class="badge badge-slate">1日必要 {{ data_get($event, 'metrics_before.daily_required_minutes', '—') }}分 → {{ data_get($event, 'metrics_after.daily_required_minutes') }}分</span>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
