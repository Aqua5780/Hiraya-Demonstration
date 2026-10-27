$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$php = Join-Path (Resolve-Path (Join-Path $projectRoot '..\.tools\php')).Path 'php.exe'

if (-not (Test-Path $php)) {
    Write-Error "Portable PHP was not found at $php. See README.md for setup instructions."
}

$port = 8000
$address = "http://127.0.0.1:$port"

Write-Host 'Starting Hiraya...' -ForegroundColor Yellow
Write-Host "Project: $projectRoot"
Write-Host "URL:     $address"
Write-Host 'Press Ctrl+C in the PHP window to stop the server.'

Start-Process -FilePath $php -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$port") -WorkingDirectory $projectRoot

Start-Sleep -Seconds 2
Start-Process $address
