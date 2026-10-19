#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."
backup_root=${MAPOS_BACKUP_DIR:-/opt/mapos-private/backups}
install -d -m 700 "$backup_root"
exec 9>"$backup_root/.backup.lock"
flock 9
destination=$(mktemp -d "$backup_root/$(date -u +%Y%m%dT%H%M%SZ).XXXXXX")
compose=(docker compose --env-file .env.local -f compose.prod.yaml)
trap 'echo "Backup incompleto; verifique o diretório criado." >&2' ERR
"${compose[@]}" exec -T db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump -u"$MYSQL_USER" --single-transaction --no-tablespaces --routines --triggers "$MYSQL_DATABASE"' </dev/null | gzip > "$destination/database.sql.gz"
paths=(application/.env .env.local)
[[ ! -f .secrets/whatsapp-token ]] || paths+=(.secrets/whatsapp-token)
for path in assets/anexos assets/arquivos assets/uploads assets/userImage; do
    [[ ! -d "$path" ]] || paths+=("$path")
done
tar -czf "$destination/files.tar.gz" "${paths[@]}"
git rev-parse HEAD > "$destination/revision"
files=(database.sql.gz files.tar.gz revision)
if [[ -n "$("${compose[@]}" ps -q whatsapp 2>/dev/null)" ]]; then
    "${compose[@]}" exec -T whatsapp node --input-type=module -e 'import {DatabaseSync,backup} from "node:sqlite"; const db=new DatabaseSync("/data/whatsapp.sqlite"); await backup(db,"/data/backup.sqlite"); db.close();' </dev/null
    "${compose[@]}" exec -T whatsapp cat /data/backup.sqlite </dev/null > "$destination/whatsapp.sqlite"
    "${compose[@]}" exec -T whatsapp rm /data/backup.sqlite </dev/null
    files+=(whatsapp.sqlite)
fi
if "${compose[@]}" exec -T php test -d /var/lib/mapos-communication </dev/null; then
    "${compose[@]}" exec -T php tar -C /var/lib/mapos-communication -czf - . </dev/null > "$destination/communication-private.tar.gz"
    tar -tzf "$destination/communication-private.tar.gz" >/dev/null
    files+=(communication-private.tar.gz)
fi
(cd "$destination" && sha256sum "${files[@]}" > SHA256SUMS)
gzip -t "$destination/database.sql.gz"
tar -tzf "$destination/files.tar.gz" >/dev/null
touch "$destination/COMPLETE"
printf '%s\n' "$destination"
