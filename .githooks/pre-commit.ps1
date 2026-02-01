#!/usr/bin/env pwsh
# PowerShell pre-commit hook: запускает phpstan и блокирует коммит при ошибках
# Уровень можно переопределить переменной окружения PHPSTAN_LEVEL (по умолчанию 9).

Write-Host "Running phpstan..."
$phpstan = if (Test-Path "vendor/bin/phpstan.bat") { "vendor/bin/phpstan.bat" } elseif (Test-Path "vendor/bin/phpstan") { "vendor/bin/phpstan" } else { "phpstan" }
$level = $env:PHPSTAN_LEVEL; if (-not $level) { $level = 9 }

& $phpstan 'analyse' '-l' $level '-c' 'phpstan.neon' '--memory-limit=1G'
if ($LASTEXITCODE -ne 0) {
    Write-Error "phpstan failed. Aborting commit."
    exit $LASTEXITCODE
}

exit 0
