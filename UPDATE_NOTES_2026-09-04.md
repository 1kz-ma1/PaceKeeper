# PaceKeeper update notes — 2026-09-04

## Main changes

- Removed the fixed "current task" dependency from the main recommendation flow.
- The dashboard now treats an active `WorkSession` as the only true "currently working" state.
- Previous work is shown separately as "前回の続き" and decays as a recommendation factor over time.
- Added `tasks.activation_cost` (1–5) for start friction / preparation cost.
- Added `tasks.next_action_note` for "次回ここから" handoff notes.
- Recommendation scoring now combines priority, time fit, activation cost, plan urgency, user state, and recent continuation.
- Added pause/resume to work sessions. Paused time is excluded from actual work time.
- "記録して終了" now ends the work session first, then asks whether the Task is done / continuing / checkpointed / blocked.
- Task completion happens in the review step, not just because the timer stopped.
- Dashboard is Recommendation First; large aggregate numbers are secondary.
- Behavior metrics stay in a learning state until enough samples/days exist.
- Very short work sessions are excluded from focus-continuity baseline calculations.
- Internal state-explanation text was removed from the action navigator.
- Added a disabled-by-default `FEATURE_PACEKEEPER_AI` feature flag for future in-app AI UI.

## Database migration

Run:

```powershell
php artisan migrate
```

New migration:

`database/migrations/2026_09_04_000006_refine_next_action_and_work_sessions.php`

It adds:

- `tasks.activation_cost`
- `tasks.next_action_note`
- `work_sessions.paused_at`
- `work_sessions.paused_seconds`
- `work_logs.work_session_id`

## Recommended local verification

```powershell
php artisan optimize:clear
php artisan migrate
npm run build
php artisan test
```

Then manually verify:

1. Dashboard recommendation is displayed before aggregate metrics.
2. Starting a Task does not make it a permanent "current Task".
3. Pause → resume excludes paused time from actual work time.
4. `記録して終了` opens the Task result review screen.
5. Choosing `完了した` completes the Task; choosing `まだ続く` does not.
6. `次回ここから` appears in later recommendation / previous-work UI.
7. Low-readiness + short available time tends to prefer low `activation_cost` Tasks.
8. A newly created account shows "作業リズムを学習中" instead of precise behavior scores.

## Verification performed in this environment

The uploaded ZIP did not contain `vendor/` or `node_modules/`, and Composer is not installed in this execution environment, so the Laravel test suite and Vite build could not be executed here.

Performed successfully:

- PHP syntax check for all PHP files under `app/`, `config/`, `database/`, `routes/`, and `tests/`
- JavaScript syntax check for `resources/js/app.js`
- JSON validation for `resources/ai/pacekeeper-contract-v2.json`
- Reference check confirming the removed `tasks.start` route is no longer used by views/tests

## Follow-up tuning after 2026-09-04 screen recording

- A Task reviewed as `まだ続く` is now treated as a strong explicit Next Action signal for the next 24 hours.
- `途中で区切った` gives a moderate continuation bonus; `あまり進まなかった` weakens automatic continuation pressure.
- Recommendation text now uses the actual fitting duration (for example, 14 minutes) instead of always describing the adaptive 25-minute budget.
- Dashboard Plan-tab selection is propagated to the Action Navigator as implicit context. If the user views AP and then opens `今日やること`, recommendations stay within AP unless the user explicitly switches back to all Plans / chooses another Plan.
- The Action Navigator shows the active Plan scope and provides `全Planから選ぶ` to remove it.
- Added a regression test ensuring an explicit `まだ続く` review can beat an unrelated higher-priority task immediately afterward.
