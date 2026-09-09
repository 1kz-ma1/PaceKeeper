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
