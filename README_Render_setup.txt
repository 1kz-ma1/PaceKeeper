PaceKeeper - Render公開用ファイル
=================================

追加先
------
PaceKeeperリポジトリのルート直下に以下を配置してください。

PaceKeeper/
├─ Dockerfile
├─ .dockerignore
└─ docker/
   └─ render-start.sh

※ README_Render_setup.txt 自体はリポジトリへ入れなくてもOKです。

Git Feature Flow例
------------------
git checkout main
git pull
git checkout -b feature/public-deployment

# 上記3ファイルをPaceKeeperへコピー後
git add Dockerfile .dockerignore docker/render-start.sh
git commit -m "chore: add Render deployment configuration"
git push -u origin feature/public-deployment

その後GitHubでPRを作成 → mainへMergeしてください。

Render側
--------
Language / Runtime: Docker
Branch: main
Dockerfile Path: ./Dockerfile

Render環境変数（DB作成後に設定）
--------------------------------
APP_NAME=PaceKeeper
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...       ← php artisan key:generate --show の結果
APP_URL=https://xxxxx.onrender.com
ASSET_URL=https://xxxxx.onrender.com

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FEATURE_PACEKEEPER_AI=false
RUN_MIGRATIONS=true

Aiven MySQLのTLSについて
------------------------
Aiven側でCA証明書が必要な構成の場合は、ca.pemをGitHubへコミットせず、
RenderのSecret Fileとして登録してください。
Laravelのconfig/database.phpはMYSQL_ATTR_SSL_CAを参照できるため、
Secret Fileの実パスを次の環境変数へ設定します。

MYSQL_ATTR_SSL_CA=<Renderで作成したca.pemの実パス>

初回公開の注意
--------------
1. Aiven MySQLを作成する
2. RenderへDB_* / APP_KEY / APP_URL等を設定する
3. RUN_MIGRATIONS=true にする
4. Deployする
5. Deploy成功後、PaceKeeperへアクセスして動作確認する

このDockerfileは現在のPaceKeeper v7
(Laravel 13 / PHP 8.4 / MySQL / Vite)に合わせています。
RenderのPORT環境変数（既定10000）で0.0.0.0へbindします。
