@php
    $collapsibleText = trim((string) ($text ?? ''));
    $emptyText = $emptyText ?? '説明はまだ設定されていません。';
    $toneClass = $toneClass ?? 'text-slate-400';
@endphp

<div class="collapsible-copy" data-collapsible-copy>
    <p class="collapsible-copy-text {{ $toneClass }}" data-collapsible-copy-text>{{ $collapsibleText !== '' ? $collapsibleText : $emptyText }}</p>
    @if ($collapsibleText !== '')
        <button type="button" class="collapsible-copy-toggle hidden" data-collapsible-copy-toggle aria-expanded="false">続きを読む</button>
    @endif
</div>
