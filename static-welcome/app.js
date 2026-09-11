(() => {
    const config = window.PACEKEEPER_WELCOME_CONFIG || {};
    const appUrl = String(config.appUrl || '').replace(/\/$/, '');
    const status = document.querySelector('[data-status]');
    const openButton = document.querySelector('[data-open]');
    const openAnyway = document.querySelector('[data-open-anyway]');
    const hints = [...document.querySelectorAll('[data-hint]')];
    const dots = [...document.querySelectorAll('[data-hint-dot]')];
    const seenKey = 'pacekeeper.welcome.seen.v1';
    const returning = localStorage.getItem(seenKey) === '1';
    let ready = false;
    let hintIndex = 0;
    let wakeTimer = null;

    if (!appUrl) {
        status.textContent = '接続先が設定されていません。';
        return;
    }

    const setHint = (index) => {
        hintIndex = index % hints.length;
        hints.forEach((hint, i) => hint.classList.toggle('is-active', i === hintIndex));
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === hintIndex));
    };

    if (!returning && hints.length > 1) {
        window.setInterval(() => setHint(hintIndex + 1), 4800);
    }

    const openApp = () => {
        localStorage.setItem(seenKey, '1');
        window.location.assign(appUrl);
    };

    const markReady = () => {
        if (ready) return;
        ready = true;
        window.clearTimeout(wakeTimer);
        status.textContent = '準備できました';
        status.classList.add('is-ready');
        openButton.disabled = false;
        openButton.textContent = 'PaceKeeperを開く';
        if (returning) window.setTimeout(openApp, 450);
    };

    const wake = async () => {
        if (ready) return;
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 7000);
        try {
            const response = await fetch(`${appUrl}/health?from=welcome&t=${Date.now()}`, {
                method: 'GET',
                mode: 'cors',
                cache: 'no-store',
                credentials: 'omit',
                signal: controller.signal,
            });
            if ((response.ok || response.status === 204) && response.headers.get('X-PaceKeeper-Ready') === '1') {
                markReady();
                return;
            }
        } catch (_) {
            // Render's own cold-start/loading response does not carry our CORS
            // header. Keep showing useful hints and retry until Laravel answers.
        } finally {
            window.clearTimeout(timeout);
        }
        wakeTimer = window.setTimeout(wake, 2200);
    };

    openButton.addEventListener('click', openApp);
    openAnyway.addEventListener('click', openApp);
    wake();
})();
