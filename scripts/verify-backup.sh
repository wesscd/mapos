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
}
trap cleanup EXIT
printf 'CREATE DATABASE `%s` CHARACTER SET utf8mb4;\n' "$database" | mysql_root
gzip -dc "$backup/database.sql.gz" | mysql_root "$database"
table_count=$(mysql_root -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$database'" </dev/null)
[[ "$table_count" -gt 20 ]] || { echo 'Restauração não contém as tabelas esperadas.' >&2; exit 1; }
mysql_root "$database" -N -B -e 'SELECT COUNT(*) FROM os; SELECT COUNT(*) FROM clientes; SELECT COUNT(*) FROM migrations;' </dev/null >/dev/null
tar -xzf "$backup/files.tar.gz" -C "$temporary"
[[ -s "$temporary/application/.env" && -s "$temporary/.env.local" ]] || exit 1
echo "Restauração isolada validada: $table_count tabelas, configurações e anexos."
