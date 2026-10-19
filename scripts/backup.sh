#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."
backup_root=${MAPOS_BACKUP_DIR:-/opt/mapos-private/backups}
install -d -m 700 "$backup_root"
destination=$(mktemp -d "$backup_root/$(date -u +%Y%m%dT%H%M%SZ).XXXXXX")
compose=(docker compose --env-file .env.local -f compose.prod.yaml)
trap 'echo "Backup incompleto; verifique o diretório criado." >&2' ERR
"${compose[@]}" exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump -u"$MYSQL_USER" --single-transaction --no-tablespaces --routines --triggers "$MYSQL_DATABASE"' </dev/null | gzip > "$destination/database.sql.gz"
paths=(application/.env .env.local)
for path in assets/anexos assets/arquivos assets/uploads assets/userImage; do
    [[ ! -d "$path" ]] || paths+=("$path")
done
tar -czf "$destination/files.tar.gz" "${paths[@]}"
git rev-parse HEAD > "$destination/revision"
(cd "$destination" && sha256sum database.sql.gz files.tar.gz revision > SHA256SUMS)
gzip -t "$destination/database.sql.gz"
tar -tzf "$destination/files.tar.gz" >/dev/null
touch "$destination/COMPLETE"
printf '%s\n' "$destination"
