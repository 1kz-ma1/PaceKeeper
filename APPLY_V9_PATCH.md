# v9 Patch 適用手順（PowerShell）

## 1. mainを最新化してfeature branchを作る

```powershell
cd C:\Herd\PaceKeeper

git status
git switch main
git pull origin main
git switch -c feature/living-roadmap-v9
```

`git status` がcleanでない場合は、未保存変更を確認してから進めてください。

## 2. ZIPを一時フォルダへ展開して上書き

ZIPをDownloadsへ保存した前提です。

```powershell
$zip = "$HOME\Downloads\PaceKeeper_2026-09-10_living_roadmap_v9_patch.zip"
$tmp = "$env:TEMP\PaceKeeper-v9-patch"

Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue
Expand-Archive -Path $zip -DestinationPath $tmp -Force

robocopy "$tmp\PaceKeeper_2026-09-10_living_roadmap_v9_patch" "C:\Herd\PaceKeeper" /E
```

`robocopy` の終了コード 1 などは「ファイルをコピーした」という正常終了を含みます。

このPatchには `package-lock.json` / Docker / `.env` を含めていないため、それらは上書きされません。

## 3. ローカル検証

```powershell
cd C:\Herd\PaceKeeper

npm ci
npm run build

php artisan optimize:clear
php artisan migrate
php artisan route:list
php artisan test
```

確認ポイント:

- Plan詳細にLiving Roadmapが表示される
- Recommendation Taskが「今ここ」で展開される
- 任意TaskからTimerを開始できる
- AI計画更新PreviewがRoadmap表示になる
- Register → 既存Guest Plan引継ぎが動く
- Logout後、旧Guest CookieだけではAccount化Private Planへ入れない
- PWA/Offline画面で前回Roadmapが表示される
- Offline Timer終了後、再接続時にWorkSessionが1件だけ同期される

## 4. commit / push

```powershell
git status
git add .
git commit -m "feat: add living roadmap and continuity experience"
git push -u origin feature/living-roadmap-v9
```

## 5. Pull Request

Base: `main`

Compare: `feature/living-roadmap-v9`

### Title

`feat: add living roadmap and continuity experience`

### Description

```md
## 概要

PaceKeeperのモバイル体験を「開く → 現在地を理解する → 前回の続きから始める → 実績から次の計画が具体化される」という一連の流れへ統合します。

今回の中心は Living Roadmap / Continuity / Instant Start / Optional Account です。

## 主な変更

### Living Roadmap
- PlanのTaskを縦型Roadmapとして可視化
- Recommendation Taskを「今ここ」として既定展開
- 他Taskはタップで詳細表示
- Roadmap上から直接Timer開始
- `next_action_note` を「次回ここから」として表示
- Task分解・具体化の系譜を保存しRoadmapで可視化

### AI更新 Roadmap Preview
- 計画更新Previewを操作一覧中心からRoadmap中心へ変更
- AI適用後のTask追加・具体化・中止・順序変更を事前に可視化
- 詳細操作一覧は補助情報として維持

### Continuity
- 最新WorkSessionから前回のTaskと再開地点を取得
- Dashboard / Planから「昨日の続き」へ戻れる導線を追加
- Account所有Planは別端末のWorkSessionも継続文脈として利用

### Instant Start / Offline
- 最新Roadmap / current TaskをIndexedDBへ保存
- Render Cold Start中やオフライン時は前回状態を即表示
- Offline画面からTimerを開始可能
- Offline WorkSessionをオンライン復帰後に同期
- `client_session_id` によって二重同期を防止

### Optional Account
- Guest利用を維持し、ログインを初回必須にしない
- Register / Login / Logout / Account画面を追加
- Guest PlanをAccount作成・ログイン時に引継ぎ
- Account化したPrivate Planの所有権をUser単位で保護
- Login/Registerにrate limitを適用

### Security
- Production Session CookieをSecure既定に変更
- Guest識別CookieもHTTPS環境ではSecure化
- `owner_token` をモデル出力から隠す

## DB変更

- `plans.user_id`
- `tasks.lineage_source_task_ids`
- `tasks.lineage_source_snapshots`
- `work_sessions.client_session_id`

## 確認

ローカルで以下を実行します。

- `npm ci`
- `npm run build`
- `php artisan migrate`
- `php artisan route:list`
- `php artisan test`

## 目的

PaceKeeperを単なるTask管理ではなく、実際の作業からRoadmap自体が具体化され、前回の文脈を翌日・別端末へ引き継ぎながら次の行動へ直接つながる伴走型の体験へ進化させるため。
```
