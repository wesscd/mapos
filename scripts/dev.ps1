param(
    [ValidateSet('up', 'stop', 'status', 'logs', 'php', 'composer')]
    [string]$Action = 'up',
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$CommandArguments
)
$ErrorActionPreference = 'Stop'
Push-Location (Split-Path $PSScriptRoot -Parent)
try {
    if (-not (Test-Path '.env.local')) {
        throw 'Configure .env.local first; see docs/AMBIENTE.md.'
    }
    $compose = @('compose', '--env-file', '.env.local', '-f', 'compose.dev.yaml')
    switch ($Action) {
        'up' {
            & docker @compose up -d --build
            if ($LASTEXITCODE -ne 0) { throw 'Unable to start containers.' }
            & docker @compose exec -T php composer install --no-interaction --prefer-dist
        }
        'stop' { & docker @compose stop }
        'status' { & docker @compose ps }
        'logs' { & docker @compose logs --tail 100 @CommandArguments }
        'php' { & docker @compose exec -T php php @CommandArguments }
        'composer' { & docker @compose exec -T php composer @CommandArguments }
    }
    if ($LASTEXITCODE -ne 0) { throw "Command failed with exit code $LASTEXITCODE." }
} finally {
    Pop-Location
}
