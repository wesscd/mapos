#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."
backup=$(realpath -- "${1:?Informe o diretório de backup}")
[[ -f "$backup/COMPLETE" ]] || { echo 'Backup incompleto.' >&2; exit 1; }
(cd "$backup" && sha256sum --status -c SHA256SUMS)
compose=(docker compose --env-file .env.local -f compose.prod.yaml)
database="mapos_restore_check_${$}"
[[ "$database" =~ ^mapos_restore_check_[0-9]+$ ]] || exit 1
temporary=$(mktemp -d /opt/mapos-private/restore-check.XXXXXX)
mysql_root() { "${compose[@]}" exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot "$@"' sh "$@"; }
cleanup() {
    printf 'DROP DATABASE IF EXISTS `%s`;\n' "$database" | mysql_root >/dev/null
    [[ "$temporary" == /opt/mapos-private/restore-check.* ]] && rm -rf -- "$temporary"
    if [[ -n "${sqlite_check:-}" ]]; then
        "${compose[@]}" exec -T -u root whatsapp rm -f -- "$sqlite_check" </dev/null
    fi
}
trap cleanup EXIT
printf 'CREATE DATABASE `%s` CHARACTER SET utf8mb4;\n' "$database" | mysql_root
gzip -dc "$backup/database.sql.gz" | mysql_root "$database"
table_count=$(mysql_root -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$database'" </dev/null)
[[ "$table_count" -gt 20 ]] || { echo 'Restauração não contém as tabelas esperadas.' >&2; exit 1; }
mysql_root "$database" -N -B -e 'SELECT COUNT(*) FROM os; SELECT COUNT(*) FROM clientes; SELECT COUNT(*) FROM migrations;' </dev/null >/dev/null
tar -xzf "$backup/files.tar.gz" -C "$temporary"
[[ -s "$temporary/application/.env" && -s "$temporary/.env.local" ]] || exit 1
if [[ -f "$backup/communication-private.tar.gz" ]]; then
    mkdir "$temporary/communication-private"
    tar -xzf "$backup/communication-private.tar.gz" -C "$temporary/communication-private"
    if [[ -f "$temporary/communication-private/smtp.json" ]]; then
        "${compose[@]}" exec -T php php -r 'json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);' < "$temporary/communication-private/smtp.json"
    fi
fi
if [[ -f "$backup/whatsapp.sqlite" ]]; then
    sqlite_check="/tmp/mapos-restore-check-${$}.sqlite"
    "${compose[@]}" cp "$backup/whatsapp.sqlite" "whatsapp:$sqlite_check" >/dev/null
    "${compose[@]}" exec -T -u root whatsapp node --input-type=module -e 'import {DatabaseSync} from "node:sqlite"; const db=new DatabaseSync(process.argv[1],{readOnly:true}); if(db.prepare("PRAGMA integrity_check").get().integrity_check!=="ok") process.exit(1); db.prepare("SELECT COUNT(*) FROM auth").get(); db.prepare("SELECT COUNT(*) FROM messages").get(); db.close();' "$sqlite_check" </dev/null
fi
echo "Restauração isolada validada: $table_count tabelas, configurações, anexos e dados privados de comunicação quando presentes."
