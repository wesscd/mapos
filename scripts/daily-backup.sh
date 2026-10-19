#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."
backup=$(bash scripts/backup.sh)
bash scripts/verify-backup.sh "$backup"
