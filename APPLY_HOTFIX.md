# Instant Start reload-loop hotfix

対象: Living Roadmap v9 適用後

## 修正内容
- Render の `/health` が成功した直後の再読込を1回だけに固定
- Service Worker の 1.2 秒フォールバックが「接続確認後の本画面読込」に再発しないよう、1回限りの network-only navigation を追加
- 複数の health probe が重なって `location.reload()` を複数予約する問題を防止
- 本画面の読込成功後 `_pk_network` を URL から履歴置換で除去
- Service Worker cache version を v3 に更新

## 適用後の確認
```powershell
npm ci
npm run build
php artisan optimize:clear
php artisan test
```

ブラウザ側では新 Service Worker を確実に読むため、一度通常画面を開いてリロードしてください。開発者ツールを使える場合は Application > Service Workers で新しい sw.js が有効になったことも確認できます。
