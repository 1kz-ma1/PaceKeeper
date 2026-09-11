<div
    class="onboarding-layer hidden"
    data-onboarding-root
    data-complete-url="{{ route('onboarding.complete') }}"
    data-skip-url="{{ route('onboarding.skip') }}"
    aria-live="polite"
>
    <div class="onboarding-blocker" data-onboarding-blocker="top"></div>
    <div class="onboarding-blocker" data-onboarding-blocker="left"></div>
    <div class="onboarding-blocker" data-onboarding-blocker="right"></div>
    <div class="onboarding-blocker" data-onboarding-blocker="bottom"></div>
    <div class="onboarding-focus-ring" data-onboarding-focus-ring aria-hidden="true"></div>

    <section class="onboarding-bubble" data-onboarding-bubble role="dialog" aria-modal="false" aria-labelledby="onboarding-title">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="onboarding-kicker" data-onboarding-progress></p>
                <h2 id="onboarding-title" class="onboarding-title" data-onboarding-title></h2>
            </div>
            <button type="button" class="onboarding-skip" data-onboarding-skip>スキップ</button>
        </div>
        <p class="onboarding-copy" data-onboarding-copy></p>
        <div class="onboarding-actions hidden" data-onboarding-actions>
            <button type="button" class="btn-primary" data-onboarding-next>次へ</button>
        </div>
    </section>
</div>

<dialog class="install-guide-dialog" data-install-guide aria-labelledby="install-guide-title">
    <section class="install-guide-card">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-sky-300">PaceKeeperをすぐ開く</p>
                <h2 id="install-guide-title" class="mt-1 text-xl font-black text-slate-50">ホーム画面に追加</h2>
            </div>
            <button type="button" class="feedback-close" data-install-guide-close aria-label="閉じる">×</button>
        </div>

        <div class="mt-5 flex items-center gap-4 rounded-2xl border border-slate-800 bg-slate-950/45 p-4">
            <img src="/icons/icon-192.png" alt="" class="h-16 w-16 rounded-2xl shadow-lg" width="64" height="64">
            <div class="min-w-0">
                <p class="font-bold text-slate-100">PaceKeeper</p>
                <p class="mt-1 text-sm leading-6 text-slate-400" data-install-guide-copy>ブラウザからホーム画面へ追加できます。</p>
            </div>
        </div>

        <div class="mt-5 hidden rounded-2xl border border-sky-400/25 bg-sky-500/10 p-4 text-sm leading-6 text-slate-200" data-install-ios-help>
            <p class="font-bold text-slate-100">iPhone / iPadの場合</p>
            <p class="mt-1">Safariの共有ボタンから「ホーム画面に追加」を選んでください。</p>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
            <button type="button" class="btn-primary flex-1 justify-center" data-install-guide-action>ホーム画面に追加</button>
            <button type="button" class="btn-secondary flex-1 justify-center" data-install-guide-later>今はしない</button>
        </div>
    </section>
</dialog>
