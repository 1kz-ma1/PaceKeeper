# PaceKeeper 2026-09-04 v3 update

今回の更新は、会社への更新報告前に「伴走アプリ」としての一連の体験を通すための改善です。

## 1. 推薦精度

- 今日一度作業しただけでそのPlanを一律に下げる処理を廃止し、「今日の必要量に対してあとどれくらい残っているか」で評価するよう変更。
- `priority`、期限、進捗差、時間枠、開始ハードル、直近作業、明示的な継続意思を組み合わせてNext Actionを算出。
- 前回作業から切り出した継続Taskは、元Taskの直近WorkSessionを引き継いで強いNext Action候補になる。

## 2. 個人ごとの推薦補正

`RecommendationPersonalizationService` を追加。

過去90日の観測データから、保存済みの固定プロフィールではなく実行時に補正を算出する。

- Recommendation Accepted / RejectedによるTask・Plan選好
- 選ばれた作業時間帯（短時間 / 中程度 / 長時間）の傾向
- Taskごとの作業開始までの時間から、ユーザー本人にとっての実効 `activation_cost` を補正
- blocked / continue / checkpoint履歴を開始ハードル補正に利用

十分な履歴がなければ補正しないため、初期ユーザーでも既存推薦がそのまま動く。

## 3. WorkSession終了後のTask再構成

「途中で区切った」とき、任意で以下を実行可能。

1. 現在のTaskを区切り地点まで完了として閉じる
2. 次の具体的なTask名と見込み時間を入力
3. `continuation_of_task_id` で元Taskと関連付けた継続Taskを生成
4. 次回Recommendationがその新Taskを候補として評価

続きTaskへ移した見積時間は元Taskの見積から差し引き、Plan全体の見積が単純に二重加算されにくいようにしている。

## 4. 結果以外の「今日の積み上げ」

Dashboardに、WorkSession / Behaviorデータから作る短い振り返りを追加。

例:

- 開始まで普段より時間がかかっても、その後作業できた
- 複数セッションに分けて合計時間を積み上げた
- 休憩を挟みながら作業を継続した
- 連続して作業開始できている
- 今日の必要量未達でも実績を残した

心理状態は断定せず、観測可能な事実だけで表現する。

## 5. 状態に応じたUI密度

十分な行動データがある場合、`UserState` に応じてDashboard表示を調整。

- `low_readiness / overloaded / undecided`: guided mode
  - Next Actionを最優先
  - 集計カードや要確認一覧を抑制
  - 今日の実績と残りだけを簡潔に表示
- `normal`: balanced mode
- `focused`: expanded mode（既存の情報量を維持し、Navigation側では継続選択肢を追加）

データ不足時は勝手にUIを変えず balanced を使用する。

## DB変更

`2026_09_04_000007_add_task_continuation_link.php`

- `tasks.continuation_of_task_id` nullable self FK

## ローカル反映

```powershell
php artisan optimize:clear
php artisan migrate
npm run build
php artisan test
```

この配布ZIPには vendor / node_modules が含まれていないため、この作業環境ではLaravelテストとVite実ビルドは実行していない。PHP構文チェック等の静的検証は実施済み。
