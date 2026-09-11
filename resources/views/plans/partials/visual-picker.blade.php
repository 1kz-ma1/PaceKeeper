@php
    $visualPlan = $visualPlan ?? null;
    $currentIcon = old('visual_icon', $visualPlan?->visual_icon ?? '');
    $currentAccent = old('accent_key', $visualPlan?->accentKey() ?? 'sky');
    $currentWorld = old('roadmap_world', $visualPlan?->roadmapWorld() ?? 'default');
    $previewTitle = $visualPlan?->title ?? '新しい計画';
@endphp
<div class="plan-visual-picker" data-plan-visual-picker>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-bold text-slate-900">見た目</p>
            <p class="mt-1 text-xs leading-5 text-slate-600">ホーム・今日・ロードマップで同じ見た目になります。</p>
        </div>
        <span class="badge badge-slate">Personalize</span>
    </div>

    <div class="plan-visual-preview plan-identity-shell mt-4" data-plan-visual-preview data-plan-accent="{{ $currentAccent }}">
        <span class="plan-identity-icon" data-plan-visual-preview-icon aria-hidden="true">{{ $currentIcon ?: ($visualPlan?->displayIcon() ?? '🧭') }}</span>
        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">プレビュー</p>
            <p class="mt-1 truncate font-black text-slate-900">{{ $previewTitle }}</p>
            <p class="mt-1 text-xs text-slate-500"><span data-plan-visual-preview-world>{{ ['default' => '🧭 ベーシック', 'study' => '📚 スタディ', 'sweet' => '🍰 スイーツ', 'halloween' => '🎃 ハロウィン', 'space' => '🪐 スペース', 'forest' => '🌲 フォレスト'][$currentWorld] ?? '🧭 ベーシック' }}</span></p>
        </div>
        <span class="plan-accent-dot" aria-hidden="true"></span>
    </div>

    <div class="plan-visual-grid mt-4">
        <label>
            <span>アイコン</span>
            <input name="visual_icon" value="{{ $currentIcon }}" maxlength="16" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100" placeholder="例：📘" data-plan-visual-icon-input>
        </label>
        <label>
            <span>アクセント</span>
            <select name="accent_key" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100" data-plan-visual-accent-input>
                @foreach (['sky' => 'Sky', 'emerald' => 'Emerald', 'violet' => 'Violet', 'amber' => 'Amber', 'rose' => 'Rose', 'cyan' => 'Cyan'] as $key => $label)
                    <option value="{{ $key }}" @selected($currentAccent === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>ロードマップの雰囲気</span>
            <select name="roadmap_world" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100" data-plan-visual-world-input>
                @foreach (['default' => '🧭 ベーシック', 'study' => '📚 スタディ', 'sweet' => '🍰 スイーツ', 'halloween' => '🎃 ハロウィン', 'space' => '🪐 スペース', 'forest' => '🌲 フォレスト'] as $key => $label)
                    <option value="{{ $key }}" @selected($currentWorld === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>
</div>
