#!/usr/bin/env bash
# Deploy the marketplace to cPanel. Run from the marketplace/ folder:  bash deploy/deploy.sh
# Uploads CODE ONLY as two small archives. Never touches the server's .env, uploads/ or storage/logs.
#   app code  -> ~/cybergaminglb-app/     (outside the web root)
#   public/   -> ~/cybergaminglb.com/     (the domain's document root)
set -euo pipefail
cd "$(dirname "$0")/.."

KEY="${DEPLOY_KEY:-$HOME/.ssh/briefflow-deploy}"
HOST="eurojeba@198.187.31.120"
PORT=21098
SSH=(ssh -i "$KEY" -p "$PORT" "$HOST")
STAMP=$(date +%Y%m%d-%H%M%S)

for f in $(find app routes config public/index.php -name '*.php'); do php -l "$f" >/dev/null; done
echo "PHP syntax OK"

tar czf /tmp/cg-app.tgz --exclude='storage/logs/*' --exclude='.env' app config database routes
tar czf /tmp/cg-public.tgz -C public --exclude='./uploads' .
scp -i "$KEY" -P "$PORT" /tmp/cg-app.tgz /tmp/cg-public.tgz "$HOST":/tmp/

"${SSH[@]}" "set -e
  mkdir -p ~/cybergaminglb-app/storage/logs ~/cybergaminglb.com/uploads/products
  cd ~/cybergaminglb-app && rm -rf app routes config && tar xzf /tmp/cg-app.tgz
  cd ~/cybergaminglb.com && rm -rf assets && tar xzf /tmp/cg-public.tgz
  rm -f /tmp/cg-app.tgz /tmp/cg-public.tgz
  cd ~/cybergaminglb-app && php database/migrate.php
  curl -sk -o /dev/null -w 'live check: HTTP %{http_code}\n' https://cybergaminglb.com/"
rm -f /tmp/cg-app.tgz /tmp/cg-public.tgz
echo "Deployed $STAMP"
