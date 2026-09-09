const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

function recordBehavior(root, eventType, payload = {}) {
    if (!root?.dataset.eventUrl || !csrfToken) return;

    fetch(root.dataset.eventUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        keepalive: true,
        body: JSON.stringify({ event_type: eventType, ...payload }),
    }).catch(() => {});
}

function formatTimer(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return hours > 0
        ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
        : `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function updateTimers() {
    document.querySelectorAll('[data-work-timer]').forEach((element) => {
        const startedAt = Date.parse(element.dataset.startedAt || '');
        if (!Number.isFinite(startedAt)) return;

        const pausedSeconds = Number(element.dataset.pausedSeconds || 0);
        const pausedAt = Date.parse(element.dataset.pausedAt || '');
        const status = element.dataset.sessionStatus || 'active';
        const now = Date.now();
        let currentPauseSeconds = 0;

        if (status === 'paused' && Number.isFinite(pausedAt)) {
            currentPauseSeconds = Math.max(0, Math.floor((now - pausedAt) / 1000));
        }

        const wallSeconds = Math.max(0, Math.floor((now - startedAt) / 1000));
        const activeSeconds = Math.max(0, wallSeconds - pausedSeconds - currentPauseSeconds);
        element.textContent = formatTimer(activeSeconds);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateTimers();
    window.setInterval(updateTimers, 1000);

    const reviewRoot = document.getElementById('workSessionReview');
    if (reviewRoot) {
        const continuationFields = reviewRoot.querySelector('[data-continuation-fields]');
        const outcomeInputs = [...reviewRoot.querySelectorAll('input[name="task_outcome"]')];
        const updateContinuationFields = () => {
            const selected = outcomeInputs.find((input) => input.checked)?.value;
            continuationFields?.classList.toggle('hidden', selected !== 'checkpoint');
        };
        outcomeInputs.forEach((input) => input.addEventListener('change', updateContinuationFields));
        updateContinuationFields();
    }

    const root = document.getElementById('behaviorDashboard');
    if (!root) return;

    let planSwitches = 0;
    let taskViews = 0;
    let workStarted = root.dataset.workStarted === '1';
    let idleNudgeShown = false;
    const enteredAt = Date.now();
    let activeTarget = 'overall';
    const viewedTaskIds = new Set();
    const tabs = [...root.querySelectorAll('[data-dashboard-tab]')];
    const panels = [...root.querySelectorAll('[data-dashboard-panel]')];

    function updateNavigationContext(planId = null) {
        const baseUrl = root.dataset.navigationUrl;
        if (!baseUrl) return;

        const url = new URL(baseUrl, window.location.origin);
        if (planId) {
            url.searchParams.set('plan_id', String(planId));
        } else {
            url.searchParams.delete('plan_id');
        }

        document.querySelectorAll('[data-navigation-link]').forEach((link) => {
            link.href = url.pathname + url.search;
        });
    }

    function recordTaskView(details) {
        const taskId = Number(details?.dataset.taskId);
        if (!taskId || viewedTaskIds.has(taskId)) return;
        viewedTaskIds.add(taskId);
        taskViews = viewedTaskIds.size;
        recordBehavior(root, 'task_viewed', {
            plan_id: Number(details.dataset.planId),
            task_id: taskId,
            metadata: { task_views: taskViews },
        });
    }

    function activateTab(target) {
        if (target === activeTarget) return;
        activeTarget = target;
        tabs.forEach((tab) => {
            const active = tab.dataset.dashboardTab === target;
            tab.classList.toggle('nav-link-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.dashboardPanel !== target));

        const tab = tabs.find((item) => item.dataset.dashboardTab === target);
        const contextPlanId = tab?.dataset.planId ? Number(tab.dataset.planId) : null;
        updateNavigationContext(contextPlanId);

        if (contextPlanId) {
            planSwitches += 1;
            recordBehavior(root, 'plan_tab_viewed', { plan_id: contextPlanId, metadata: { plan_switches: planSwitches } });
            const panel = panels.find((item) => item.dataset.dashboardPanel === target);
            recordTaskView(panel?.querySelector('[data-task-view]'));
        }
    }

    updateNavigationContext(null);
    tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.dashboardTab)));
    root.querySelectorAll('[data-open-dashboard-tab]').forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.openDashboardTab)));
    root.querySelectorAll('[data-task-view]').forEach((element) => element.addEventListener('click', () => recordTaskView(element)));
    document.querySelectorAll('[data-work-start-form]').forEach((form) => form.addEventListener('submit', () => { workStarted = true; }));

    window.setInterval(() => {
        const elapsedSeconds = Math.floor((Date.now() - enteredAt) / 1000);
        if (document.visibilityState !== 'visible' || workStarted || elapsedSeconds < 90 || planSwitches + taskViews < 2) return;
        if (!idleNudgeShown) {
            root.querySelector('[data-idle-nudge]')?.classList.remove('hidden');
            idleNudgeShown = true;
        }
        recordBehavior(root, 'dashboard_idle', {
            metadata: {
                elapsed_seconds: elapsedSeconds,
                plan_switches: planSwitches,
                task_views: taskViews,
                page_visible: true,
                work_started: false,
            },
        });
    }, 15000);
});

// -----------------------------------------------------------------------------
// Mobile app shell enhancements
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const mobileBack = document.querySelector('[data-mobile-back]');
    mobileBack?.addEventListener('click', () => {
        if (window.history.length > 1) {
            window.history.back();
            return;
        }
        window.location.href = '/';
    });

    document.querySelectorAll('[data-auto-toast]').forEach((toast) => {
        window.setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(0.5rem)';
            window.setTimeout(() => toast.remove(), 220);
        }, 3600);
    });

    const loadingOverlay = document.querySelector('[data-route-loading]');
    let loadingTimer = null;

    const showLoading = () => {
        if (!loadingOverlay) return;
        window.clearTimeout(loadingTimer);
        loadingTimer = window.setTimeout(() => {
            loadingOverlay.classList.add('is-visible');
            loadingOverlay.setAttribute('aria-hidden', 'false');
        }, 220);
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.target === '_blank') return;
        showLoading();
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        if (link.href.startsWith('mailto:') || link.href.startsWith('tel:')) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;
        showLoading();
    });

    window.addEventListener('pageshow', () => {
        window.clearTimeout(loadingTimer);
        loadingOverlay?.classList.remove('is-visible');
        loadingOverlay?.setAttribute('aria-hidden', 'true');
    });

    document.querySelectorAll('[data-candidate-carousel]').forEach((carousel) => {
        const shell = carousel.querySelector('[data-candidate-shell]');
        const toggle = carousel.querySelector('[data-candidate-toggle]');
        const track = carousel.querySelector('[data-candidate-track]');
        const cards = Array.from(carousel.querySelectorAll('[data-candidate-card]'));
        const dots = Array.from(carousel.querySelectorAll('[data-candidate-dot]'));
        const eventUrl = carousel.dataset.eventUrl;
        const viewed = new Set();

        const setActive = (index) => {
            dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === index));
            const card = cards[index];
            if (!card) return;
            const taskId = Number(card.dataset.taskId || 0);
            if (!taskId || viewed.has(taskId) || !eventUrl || !csrfToken) return;
            viewed.add(taskId);
            fetch(eventUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    event_type: 'task_viewed',
                    plan_id: Number(card.dataset.planId || 0) || null,
                    task_id: taskId,
                    metadata: { source: 'navigation_candidate_carousel', candidate_index: index },
                }),
                keepalive: true,
            }).catch(() => {});
        };

        toggle?.addEventListener('click', () => {
            const opening = !shell?.classList.contains('is-open');
            shell?.classList.toggle('is-open', opening);
            toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
            toggle.textContent = opening ? '候補を閉じる' : '別候補を見る';
            if (opening) {
                setActive(0);
                window.setTimeout(() => shell?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 30);
            }
        });

        if (track && cards.length > 0 && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                if (!visible || visible.intersectionRatio < 0.62) return;
                const index = cards.indexOf(visible.target);
                if (index >= 0) setActive(index);
            }, { root: track, threshold: [0.62, 0.8] });
            cards.forEach((card) => observer.observe(card));
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                cards[index]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
                setActive(index);
            });
        });
    });

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
});
