# PaceKeeper Static Welcome

Render Free Web Service がスリープ中でも、先に表示できる静的な入口です。

## Render Static Site 設定

- Repository: PaceKeeper と同じ GitHub repository
- Root Directory: `static-welcome`
- Build Command: 空欄
- Publish Directory: `.`

公開後は、その Static Site の URL を新規ユーザーへ共有します。

`config.js` の `appUrl` は Laravel 本体の公開 URL です。現在は `https://pacekeeper-d3mm.onrender.com` を設定しています。

Static Site は起動直後から `/health` をポーリングします。Laravel が実際に応答できるまで、PaceKeeper の特徴を数秒ごとに切り替えて表示します。準備完了後に「PaceKeeperを開く」が有効になります。2回目以降は準備完了後に自動で本体へ移動します。
