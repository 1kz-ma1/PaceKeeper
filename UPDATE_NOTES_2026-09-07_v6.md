# PaceKeeper v6 - 今日やることを「すぐ始める」画面へ

## 目的
毎日の作業開始ハードルを下げ、PaceKeeper自身の操作が行動開始を邪魔しないようにする。

## 主な変更
- 「今日やること」を開いた時点で、質問フローより先にRecommendationServiceの第1候補を表示
- 推薦Taskカード内に開始前タイマー表示（00:00）と「このまま開始」ボタンを配置
- 開始は1回の操作でWorkSessionへ接続し、その瞬間から計測開始
- 「別のTaskにする」で次候補へ即切り替え
- 既存の意図・利用可能時間の選択フローは削除せず「条件を変えて選ぶ」という副導線へ移動
- DashboardからPlan指定で遷移した場合も、そのPlan内の第1候補を即表示
- 推薦表示/チェンジ/開始の既存BehaviorEventを維持し、個人補正へ利用
- WorkSession開始までの時間計測ではRecommendationShownも開始点候補として扱う
- UserStateの開始待ち判定でも、直接表示されたRecommendationShownをNavigationStartedと同様の開始シグナルとして扱う
- Dashboard / 計画・実績側の文言も「決める」より「おすすめから始める」へ寄せ、導線の役割を統一

## DB変更
なし。migration不要。

## ローカル確認
```powershell
php artisan optimize:clear
npm run build
php artisan test
```

## 次回検討候補
- AI反映を後回しにしたWorkSessionを一覧化する「未反映作業 / AI Requests」
- Timerを使わずに行った作業をAI経由で後から取り込む入口の見せ方改善
- 今日やること画面での推薦チェンジ履歴を実利用データで評価し、推薦重みを調整
- recommendedMinutesをユーザーごとの実績時間から補正する仕組み
