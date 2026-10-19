$ErrorActionPreference = 'Stop'
Push-Location (Split-Path $PSScriptRoot -Parent)
try {
    $compose = @('compose', '--env-file', '.env.local', '-f', 'compose.dev.yaml')
    & docker @compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "CREATE DATABASE IF NOT EXISTS mapos_fork_test CHARACTER SET utf8mb4; GRANT ALL ON mapos_fork_test.* TO mapos;"'
    if ($LASTEXITCODE -ne 0) { throw 'Unable to prepare the test database.' }
    & docker @compose exec -T php php tests/run.php
    if ($LASTEXITCODE -ne 0) { throw 'Integration tests failed.' }
} finally {
    Pop-Location
}
