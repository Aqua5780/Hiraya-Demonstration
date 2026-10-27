param(
    [switch]$Install
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

if ($Install) {
    ..\.tools\php\php.exe ..\.tools\composer.phar install --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

    npm ci
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

Write-Host 'Auditing PHP dependencies...'
..\.tools\php\php.exe ..\.tools\composer.phar audit --locked
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host 'Auditing production Node dependencies...'
npm audit --omit=dev
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host 'Dependency audit passed.'
