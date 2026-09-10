# PaceKeeper v9 — Living Roadmap / Instant Start / Continuity / Optional Account

## 今回のテーマ

「開いた瞬間に現在地が分かり、昨日の文脈を引き継ぎ、待たずに作業を始められ、使い続けたデータを失わない」ことを中心に、モバイル体験を一本化する更新です。

## 1. Living Roadmap

- Plan画面を、単純なTask一覧ではなく縦型Roadmap中心のUIへ変更。
- RecommendationServiceが選んだTaskを「今ここ」として既定展開。
- 完了・未着手・中止・現在TaskをRoadmap上で区別。
- 他Taskはタップで詳細展開し、その場からTimer開始可能。
- `next_action_note` を「次回ここから」としてRoadmap上に表示。
- Taskの分解・具体化履歴を lineage として保存・表示。
- 元Task → 分解後Taskの関係を `lineage_source_task_ids` / `lineage_source_snapshots` で保持。

## 2. AI計画更新のRoadmap Preview

- AI更新内容を操作一覧だけで見せず、「反映後にRoadmapがどう変わるか」を主表示に変更。
- create/update/cancel/reorder等をRoadmapServiceで仮適用し、追加・具体化・中止・順序変更を可視化。
- 詳細な操作一覧は補助情報として残す。

## 3. Continuity / Resume

- 最新WorkSessionから前回のTask・再開地点を取得。
- Dashboard / Plan上で「前回の続き」を提示。
- Account所有Planでは、別端末のWorkSessionも継続文脈として利用可能。

## 4. Instant Start / Offline

- IndexedDBへ最新のPlan / Roadmap / current Taskの安全なスナップショットを保存。
- Service Workerはサーバー応答が遅い・オフライン時に `/offline.html` を即時表示。
- Offline画面では前回Roadmapと「今ここ」を表示し、Task Timerを開始可能。
- Offline Timer終了結果は端末にPending保存し、オンライン復帰後 `/offline/work-sessions/sync` へ同期。
- `client_session_id` により同一Offline Sessionの二重登録を防止。

## 5. Optional Account

- 初回ログイン必須にはせず、従来通りGuestですぐ利用可能。
- 登録 / ログイン / ログアウト / Account画面を追加。
- Account作成・ログイン時に、現在のGuest Cookieで所有しているPlanをAccountへ引き継ぎ。
- Account化したPrivate Planは旧Guest Cookieだけでは再取得できないよう所有権を強化。
- `users` のLaravel標準認証とhashed password castを利用。
- Login/Registerにはrate limitを適用。

## 6. Production / Security

- productionではSession CookieをSecure既定に変更。
- Guest識別Cookieもproduction/HTTPSではSecureを使用。
- `Plan::owner_token` はモデルのhidden属性に追加済み。

## DB変更

新規migration:

`2026_09_10_000009_add_accounts_and_offline_session_support.php`

追加内容:

- `plans.user_id` nullable foreign key
- `tasks.lineage_source_task_ids` JSON nullable
- `tasks.lineage_source_snapshots` JSON nullable
- `work_sessions.client_session_id` UUID nullable unique

## 検証済み

- 全PHPファイル `php -l`: OK
- `resources/js/app.js` `node --check`: OK
- `pacekeeper-contract-v2.json`: JSON parse OK
- `manifest.webmanifest`: JSON parse OK

Laravelの統合テストは、この作業環境にComposer/vendorがないため未実行です。ローカル適用後に `php artisan test` を最終ゲートにしてください。

## Patch ZIPについて

このZIPは既存の正常なv8環境へ重ねる **差分Patch** です。

意図的に以下を含めていません。

- `.env`
- `.env.example`
- `package-lock.json`
- `package.json`
- `Dockerfile`
- `.dockerignore`
- `docker/render-start.sh`
- `AppServiceProvider.php`

前回のlockfile/Render設定上書き事故を避けるため、依存・デプロイ設定は現在の正常なリポジトリ側を維持してください。
