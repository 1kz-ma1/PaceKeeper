# PaceKeeper v5 - Company candidate final UX pass

## 今回の目的
会社への更新報告前に、PaceKeeper側でユーザーへ構造化入力を要求しすぎていた「計画更新」と、タイマー終了後だけ別ロジックでTask状態を確定していた「作業結果記録」を一本化した。

## 1. 計画更新画面を最小入力化
- 作業日、作業時間、関連Task、難易度、発見、判断依頼などの事前入力を削除。
- ユーザーが入力するのは「先に伝えておきたいこと（任意）」だけ。
- 空欄でもAI用プロンプトを生成可能。
- プロンプトは、現在の会話に既知情報があれば再利用し、情報がなければ最初に自由形式で今回の出来事を尋ねるよう指示。
- 確定に不要な質問はせず、不明項目を更新しないことで安全に処理できる場合はそのままJSONを返す方針を維持。

## 2. タイマー終了後の4択を廃止
- 「完了した / まだ続く / 途中で区切った / あまり進まなかった」のユーザー側判定UIを廃止。
- `記録して終了` では、PaceKeeperが確実に把握できる以下の事実のみ即時保存。
  - Plan / Task
  - 開始・終了
  - 実作業時間
  - 一時停止時間
  - WorkLog
- Taskの進捗、完了、残り時間、次のAction、Task再構成はこの時点では変更しない。
- 終了後は同じ「計画を更新」画面へ遷移し、AI用プロンプトを生成できる。

## 3. 計画更新パイプラインを統合
- 通常の計画更新とWorkSession終了後の更新が同じ `PlanReviewAssistantController` / JSON preview / apply を利用するよう統合。
- WorkSession経由では、PaceKeeperが記録済みの事実をプロンプトへ自動追加。
- 保存済みWorkLogと同じ作業を `record_result` で重複登録しないようプロンプトで明示。
- 作業内容をAIが現在の会話から把握できない場合は、自由形式の質問を原則1回だけ行うよう指示。
- 旧4択の `task_outcome` をRecommendation / Personalizationの判断材料から除去。
- 推薦はTask状態、継続Task、直近WorkSession、行動履歴などの事実ベースへ統一。
- 旧Review POSTは互換用に残し、Taskを書き換えず共通計画更新フローへリダイレクトする。

## 4. UX
- WorkSession終了後は、記録済みTask・実作業時間・一時停止時間を表示。
- AI連携を今行わない場合は「今は戻る（作業記録は保存済み）」で離脱可能。
- 通常の計画更新もWorkSession経由も、プロンプト生成・JSON previewは既存の非同期UIを利用しスクロール位置を維持。

## DB変更
なし。v4までのmigrationが適用済みなら追加migrationは不要。

## 検証
- PHP 87ファイル: `php -l` で構文エラーなし。
- resources/js: `node --check` で構文エラーなし。
- 変更Bladeの主要ディレクティブ数を確認。
- 変更画面・Controller内のroute参照が `routes/web.php` のroute nameに存在することを確認。
- Feature testを更新し、以下を仕様として固定:
  - Pause時間を実作業時間へ含めない。
  - タイマー終了だけではTask状態を変更しない。
  - WorkSession終了後は共通計画更新へ遷移する。
  - 保存済みWorkLogをAIプロンプトが重複登録しないよう指示する。
  - 通常計画更新は構造化入力なし・空欄からプロンプト生成できる。

### この環境で未実行
- `php artisan test`: ZIPにvendorが含まれずComposer実行環境がないため未実行。
- `npm run build`: offline npm cacheにはLinux用Rolldown optional native bindingがなく、このコンテナでは実ビルド不可。`npm ci --offline` 自体は成功。ローカルWindows環境で実行すること。

## ローカル確認
```powershell
php artisan optimize:clear
npm install
npm run build
php artisan test
```

## 次回の作業候補（今回で実装しない）
### 未反映WorkSession / AI Requests キュー
タイマー終了後に「今は戻る」を選んだ場合、作業事実は保存されるが「このWorkSessionはまだAIで計画へ意味付けしていない」という状態を明示的には管理していない。

次回は以下を検討する。
- AI反映待ちWorkSessionを明示的に保持。
- Dashboardに「計画へ未反映の作業結果が1件」のような軽い導線を表示。
- 後からPC/スマホのどちらでも該当WorkSessionのAI用プロンプトを再生成。
- 将来の External AI Bridge / AI Requests と同じ仕組みへ発展できる構造にする。

これにより「あとで」を本当に安全な非同期ハンドオフへでき、クロスデバイスAI利用にもつながる。
