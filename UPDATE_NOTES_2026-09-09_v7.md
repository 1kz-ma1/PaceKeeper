# PaceKeeper v7 update notes — 2026-09-09

次回報告に向けた実装候補のうち、既存v6の導線を壊さずに入れられるものから実装した。

## 1. 未反映WorkSession
- Timer終了時、作業時間など確定できる事実をWorkLogへ保存した後、`needs_plan_update` を立てる。
- AI反映を後回しにしたWorkSessionはDashboardへ最大5件表示。
- 後から対象WorkSessionの共通Plan Update画面へ戻れる。
- AI JSONを実際に反映した時点で `needs_plan_update=false` / `plan_updated_at` を記録する。

## 2. Timerを使わなかった作業の事後反映
- サポートメニューに「やったことを後から反映する」を追加。
- 既存のPlan Updateパイプラインを再利用し、作業時間が曖昧でもAIへ最新状況を説明できる。
- WorkLogを無理に作らず、確実なTask/Plan更新だけを返す既存方針を維持。

## 3. 作業可能時間（Availability）
- `plan_availability_rules`: 曜日ごとの作業可能分数。
- `plan_availability_overrides`: 特定日の例外分数。
- 細かな時刻表ではなく「その日に何分使えるか」を管理する。
- `PlanProgressService` はAvailability設定時、残り日数の単純割りではなく残り作業可能時間に対する必要負荷率を計算。
- 今日の目安は `今日のavailable_minutes × 必要負荷率`。
- 残作業量が残り作業可能時間を超える場合は `作業時間不足` と判定。
- Plan詳細に作業可能時間、残りcapacity、必要負荷率を表示。

## 4. AI JSONからAvailability更新
Plan Update JSONに `update_availability` を追加。

例:
```json
{
  "type": "update_availability",
  "weekly_schedule": {
    "tuesday": 90,
    "thursday": 90,
    "saturday": {"available_minutes": 120, "is_optional": true}
  },
  "overrides": [
    {"date": "2026-12-24", "available_minutes": 180, "note": "冬休み"}
  ]
}
```

ユーザーは曜日・授業・休暇などをAIへ自然文で説明し、PaceKeeper側ではJSON Preview後に反映する。

## 5. recommendedMinutesの個人補正
- 過去90日の完了WorkSessionから実作業時間を収集。
- 3件以上ある場合は中央値を個人の作業セッション傾向として使用。
- UserStateから算出した基準時間と50:50で混ぜ、急激に最適化しすぎない。
- Availability設定時は、その日の残り作業可能時間もrecommendedMinutesの上限として使用。

## 6. 動的Task再構成の土台
- AI Promptへ「大きなTaskの中で完了部分と残作業が分離できた場合」の再構成ルールを追加。
- 原則、元Taskを今後の残作業へ具体化し、完了済み部分を独立したdone Taskとして保存できる。
- `progress_origin=inherited_task` + `source_task_ids` から、1つの元Taskを参照する新Taskには `continuation_of_task_id` を自動設定。
- 分割前後で想定・残り時間を二重計上しないようAIへ明示。
- `activation_cost` もTask追加/更新JSONで扱えるよう拡張。

## Migration
`2026_09_09_000008_add_availability_and_pending_update_support.php`

ローカル適用時:
```bash
php artisan migrate
php artisan optimize:clear
npm run build
php artisan test
```
