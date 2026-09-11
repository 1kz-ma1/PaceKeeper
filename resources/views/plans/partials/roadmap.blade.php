@php
    $roadmapPlan = $roadmapPlan ?? $plan ?? null;
    $roadmapMode = $roadmapMode ?? 'plan';
    $roadmapPreview = $roadmapMode === 'preview';
    $roadmapPlanId = $roadmapPreview ? 'preview' : ($roadmapPlan?->id ?? 'generic');
    $roadmapAccent = $roadmapPlan?->accentKey() ?? 'sky';
    $roadmapWorld = $roadmapPlan?->roadmapWorld() ?? 'default';
    $worldLabels = [
        'default' => ['🧭', 'ベーシック'],
        'study' => ['📚', 'スタディ'],
        'sweet' => ['🍰', 'スイーツ'],
        'halloween' => ['🎃', 'ハロウィン'],
        'space' => ['🪐', 'スペース'],
        'forest' => ['🌲', 'フォレスト'],
    ];
    $worldInfo = $worldLabels[$roadmapWorld] ?? $worldLabels['default'];
@endphp

<div class="plan-identity-shell" data-plan-accent="{{ $roadmapAccent }}" data-roadmap-view-root data-roadmap-plan-id="{{ $roadmapPlanId }}">
    <div class="roadmap-view-toolbar">
        <span class="roadmap-world-label"><span aria-hidden="true">{{ $worldInfo[0] }}</span>{{ $worldInfo[1] }}</span>
        <div class="roadmap-view-switch" role="group" aria-label="ロードマップ表示切替">
            <button type="button" class="roadmap-view-button" data-roadmap-view-button="map" aria-pressed="false">マップ</button>
            <button type="button" class="roadmap-view-button is-active" data-roadmap-view-button="list" aria-pressed="true">リスト</button>
        </div>
    </div>

    <div data-roadmap-view-panel="map" hidden>
        @include('plans.partials.roadmap-map', [
            'roadmap' => $roadmap,
            'roadmapPlan' => $roadmapPlan,
            'roadmapCanEdit' => $roadmapCanEdit ?? false,
            'roadmapMode' => $roadmapMode,
            'roadmapRecommendedMinutes' => $roadmapRecommendedMinutes ?? null,
            'roadmapRecommendationReasons' => $roadmapRecommendationReasons ?? [],
            'roadmapWorld' => $roadmapWorld,
        ])
    </div>

    <div data-roadmap-view-panel="list">
        @include('plans.partials.roadmap-list', [
            'roadmap' => $roadmap,
            'roadmapPlan' => $roadmapPlan,
            'roadmapCanEdit' => $roadmapCanEdit ?? false,
            'roadmapMode' => $roadmapMode,
            'roadmapRecommendedMinutes' => $roadmapRecommendedMinutes ?? null,
            'roadmapRecommendationReasons' => $roadmapRecommendationReasons ?? [],
        ])
    </div>
</div>
