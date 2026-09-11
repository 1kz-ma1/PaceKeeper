@php
    $roadmapMode = $roadmapMode ?? 'plan';
    $roadmapCanEdit = $roadmapCanEdit ?? false;
    $roadmapPlan = $roadmapPlan ?? $plan ?? null;
    $roadmapPreview = $roadmapMode === 'preview';
    $roadmapRecommendedMinutes = $roadmapRecommendedMinutes ?? null;
    $roadmapRecommendationReasons = $roadmapRecommendationReasons ?? [];
    $roadmapWorld = $roadmapWorld ?? ($roadmapPlan?->roadmapWorld() ?? 'default');
    $roadmapNodes = collect($roadmap['nodes'] ?? [])->values();
    $positions = ['center', 'left', 'right', 'center', 'right', 'left'];
    $changeLabels = [
        'created' => '追加',
        'updated' => '具体化',
        'cancelled' => '中止',
        'kept' => '維持',
        'reordered' => '順序変更',
    ];
    $decorations = match ($roadmapWorld) {
        'study' => ['📚', '✏️', '📝'],
        'sweet' => ['🍰', '🍬', '🧁'],
        'halloween' => ['🎃', '🕯️', '👻'],
        'space' => ['🪐', '✦', '🚀'],
        'forest' => ['🌲', '🍃', '⛰️'],
        default => ['✦', '·', '✧'],
    };
@endphp

<div class="roadmap-map-stage" data-roadmap-map data-world="{{ $roadmapWorld }}">
    <span class="roadmap-world-decoration one" aria-hidden="true">{{ $decorations[0] }}</span>
    <span class="roadmap-world-decoration two" aria-hidden="true">{{ $decorations[1] }}</span>
    <span class="roadmap-world-decoration three" aria-hidden="true">{{ $decorations[2] }}</span>

    @forelse ($roadmapNodes as $index => $node)
        @php
            $isCurrent = (bool) ($node['is_current'] ?? false);
            $isDone = ($node['status'] ?? null) === 'done';
            $isCancelled = ($node['status'] ?? null) === 'cancelled';
            $isChanged = ($node['change_type'] ?? 'unchanged') !== 'unchanged';
            $isFar = (bool) ($node['is_far_future'] ?? false);
            $position = $positions[$index % count($positions)];
            if ($node['is_lineage_child'] ?? false) {
                $position = $index % 2 === 0 ? 'right' : 'left';
            }
            $nextNode = $roadmapNodes->get($index + 1);
            $nextPosition = $nextNode ? $positions[($index + 1) % count($positions)] : $position;
            if ($nextNode && ($nextNode['is_lineage_child'] ?? false)) {
                $nextPosition = ($index + 1) % 2 === 0 ? 'right' : 'left';
            }
            $xByPosition = ['left' => 28, 'center' => 50, 'right' => 72];
            $mapX = $xByPosition[$position] ?? 50;
            $nextMapX = $xByPosition[$nextPosition] ?? 50;
            $marker = $isDone ? '✓' : ($isCancelled ? '×' : ($isCurrent ? '◎' : ($index + 1)));
        @endphp
        <div class="roadmap-map-row map-pos-{{ $position }}" style="--map-x: {{ $mapX }}%; --map-next-x: {{ $nextMapX }}%;">
            @if (! $loop->last)
                <svg class="roadmap-map-connector" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <line x1="{{ $mapX }}" y1="14" x2="{{ $nextMapX }}" y2="100"></line>
                </svg>
            @endif
            <details
                class="roadmap-map-stop {{ $isCurrent ? 'is-current' : '' }} {{ $isDone ? 'is-done' : '' }} {{ $isCancelled ? 'is-cancelled' : '' }} {{ $isFar ? 'is-far' : '' }} {{ ($node['is_lineage_child'] ?? false) ? 'is-lineage-child' : '' }} {{ ($node['is_lineage_source'] ?? false) ? 'is-lineage-source' : '' }}"
                data-map-stop
                @if ($isCurrent || ($roadmapPreview && $isChanged)) open @endif
            >
                <summary aria-label="{{ $node['title'] }}の詳細を{{ $isCurrent ? '表示中' : '開く' }}">
                    <span class="roadmap-map-marker" aria-hidden="true">{{ $marker }}</span>
                    <span class="roadmap-map-title"><span>{{ $node['title'] }}</span></span>
                </summary>
                <div class="roadmap-map-detail">
                    <div class="roadmap-map-status">
                        @if ($isCurrent)<span class="roadmap-map-now">今ここ</span>@endif
                        <span>{{ $node['status_label'] }}</span>
                        @if ($node['is_last_worked'] ?? false)<span>・前回の続き</span>@endif
                        @if ($node['is_lineage_child'] ?? false)<span>・実績から具体化</span>@endif
                        @if ($roadmapPreview && $isChanged)<span>・{{ $changeLabels[$node['change_type']] ?? '変更' }}</span>@endif
                    </div>
                    <h3 class="mt-2 text-base font-bold text-slate-50">{{ $node['title'] }}</h3>

                    @if (! empty($node['next_action_note']))
                        <div class="roadmap-resume-note mt-3">
                            <p class="text-[11px] font-black uppercase tracking-[0.13em] text-sky-300">次回ここから</p>
                            <p class="mt-1 text-sm leading-6 text-slate-100">{{ $node['next_action_note'] }}</p>
                        </div>
                    @elseif (! empty($node['description']))
                        <div class="mt-3">
                            @include('layouts.partials.collapsible-text', [
                                'text' => $node['description'],
                                'toneClass' => 'text-sm leading-6 text-slate-300',
                                'emptyText' => '',
                            ])
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        @if ($isCurrent && ! $roadmapPreview && $roadmapRecommendedMinutes)<span class="badge badge-green">今回 {{ $roadmapRecommendedMinutes }}分</span>@endif
                        <span class="badge badge-slate">残り {{ $node['remaining_minutes'] }}分</span>
                        @unless ($isFar)<span class="badge badge-slate">{{ $node['progress_percent'] }}%</span>@endunless
                    </div>

                    @if (! empty($node['source_task_titles']))
                        <p class="mt-3 text-xs leading-5 text-emerald-300">{{ implode(' / ', $node['source_task_titles']) }} から具体化</p>
                    @endif
                    @if (! empty($node['lineage_children']))
                        <p class="mt-2 text-xs leading-5 text-emerald-300">この地点から道が分岐：{{ collect($node['lineage_children'])->pluck('title')->implode(' / ') }}</p>
                    @endif

                    @if ($roadmapCanEdit && ! $roadmapPreview && ($node['startable'] ?? false) && ! empty($node['task_id']))
                        <form method="POST" action="{{ route('work_sessions.start') }}" class="mt-4" data-work-start-form>
                            @csrf
                            <input type="hidden" name="task_id" value="{{ $node['task_id'] }}">
                            <input type="hidden" name="source" value="roadmap">
                            @if ($isCurrent && $roadmapRecommendedMinutes)<input type="hidden" name="intended_minutes" value="{{ $roadmapRecommendedMinutes }}">@endif
                            <button type="submit" class="{{ $isCurrent ? 'btn-primary' : 'btn-secondary' }} w-full">{{ ($node['is_last_worked'] ?? false) ? '続きから開始' : 'このタスクを開始' }}</button>
                        </form>
                    @endif
                </div>
            </details>
        </div>
    @empty
        <div class="empty-state relative z-10">
            <p class="font-bold text-slate-100">まだ道がありません。</p>
            <p class="mt-2 text-sm leading-6 text-slate-400">ざっくりしたタスクから始めても大丈夫です。使いながら道筋を整えていけます。</p>
            @if ($roadmapCanEdit && $roadmapPlan)
                <a href="{{ route('plans.ai_task_assistant.show', $roadmapPlan) }}" class="btn-primary mt-4">最初の道を作る</a>
            @endif
        </div>
    @endforelse
</div>
