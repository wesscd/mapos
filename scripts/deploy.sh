#!/usr/bin/env bash
set -Eeuo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."
branch=${1:-codex/deployment-development}
git check-ref-format --branch "$branch" >/dev/null
[[ "$(git remote get-url origin)" == https://github.com/wesscd/mapos.git ]] || { echo 'Remoto de implantação inesperado.' >&2; exit 1; }
git diff --quiet && git diff --cached --quiet || { echo 'Existem alterações locais; implantação cancelada.' >&2; exit 1; }
git fetch origin "$branch"
target=$(git rev-parse FETCH_HEAD)
git merge-base --is-ancestor HEAD "$target" || { echo 'A implantação exige avanço linear do fork.' >&2; exit 1; }
backup=$(bash scripts/backup.sh)
bash scripts/verify-backup.sh "$backup"
touch .maintenance
trap 'echo "Implantação interrompida. Preserve o backup e verifique os logs antes de remover .maintenance." >&2' ERR
git merge --ff-only "$target"
compose=(docker compose --env-file .env.local -f compose.prod.yaml)
"${compose[@]}" exec -T -e COMPOSER_ALLOW_SUPERUSER=1 php composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader </dev/null
"${compose[@]}" exec -T php composer check-platform-reqs --no-dev </dev/null
"${compose[@]}" exec -T php php index.php tools migrate </dev/null
"${compose[@]}" exec -T -u root php sh -c 'mkdir -p application/cache/htmlpurifier; chown -R www-data:www-data application/cache application/logs; chgrp www-data application/.env; chmod 660 application/.env' </dev/null
"${compose[@]}" up -d --build --no-deps php nginx
"${compose[@]}" exec -T nginx nginx -t </dev/null
"${compose[@]}" exec -T nginx nginx -s reload </dev/null
rm -- .maintenance
curl --fail --silent --show-error -o /dev/null https://mapos.ultraitsm.online/index.php/login
echo "Implantação concluída: $target. Backup: $backup"
