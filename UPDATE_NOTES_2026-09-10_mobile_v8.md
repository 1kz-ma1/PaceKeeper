# PaceKeeper Mobile UI v8

## Theme
スマホで「PC画面を縮めただけ」に見えないことを優先し、親指操作・情報密度・画面遷移の軽さを見直した。

## Main changes
- モバイル専用の下部ガラスナビゲーション（計画 / 今日 / ホーム / 公開）
- iPhone safe-area 対応と短いナビラベルで不自然な改行を解消
- モバイル上部ヘッダーを最小化し、詳細画面には戻る導線を追加
- Today の最有力候補は1件を強く提示したまま維持
- 「別のTask」はページ遷移ではなく、最大3候補の横スワイプ・snapカードで比較
- 候補カードの閲覧を `task_viewed` として記録し、既存の推薦学習へ接続
- Timer をモバイルの集中画面として再設計。ナビを隠し、時間と開始/停止操作を主役化
- Dashboard の主要数値はスマホでは折りたたみ、Planタブは横スクロール + sticky
- Plan/実績系の指標カードはスマホで横スクロールできる metric strip に変更
- フォームのタップ領域を拡大し、iPhoneの入力時ズームを避けるため16px以上に調整
- 保存/更新メッセージをモバイルToast化
- 内部画面遷移・フォーム送信時に軽量loading overlayを表示
- PWA manifest / service worker / app iconを追加
  - 個人データを含むHTML/APIレスポンスはservice workerでキャッシュしない
- Render公開設定を同梱
  - Docker/Vite build
  - 起動時migration
  - productionでHTTPS URLを強制しMixed Contentを防止

## Files mainly changed
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/partials/mobile-nav.blade.php`
- `resources/views/navigation/index.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/work_sessions/active.blade.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `app/Http/Controllers/NavigationController.php`
- `app/Providers/AppServiceProvider.php`
- `public/manifest.webmanifest`
- `public/sw.js`
- `public/icons/*`

## Verification
- `php -l app/Http/Controllers/NavigationController.php`: OK
- `php -l app/Providers/AppServiceProvider.php`: OK
- `node --check resources/js/app.js`: OK
- package-lock / manifest JSON parse: OK
- This environment could not complete `npm ci` because registry access timed out / cache was incomplete. Run `npm ci && npm run build` locally before PR merge.

## Manual checks recommended
1. iPhone width (390px前後)で下部ナビが1行に収まる
2. Todayで「別候補を見る」→横スワイプ→2/3件目から開始できる
3. Timer中は下部ナビが消え、pause/resume/completeが押しやすい
4. DashboardのPlanタブが横スクロールし、選択後もTodayへのPlan contextが維持される
5. Plan詳細の指標カードが横スクロールできる
6. ホーム画面追加後、standaloneで起動できる
7. ConsoleにMixed Content / asset 404 が出ない
