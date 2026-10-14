# Inicia Energhost en Windows sin XAMPP.
# Uso: powershell -ExecutionPolicy Bypass -File .\scripts\start-local.ps1

$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz

if (-not (Test-Path ".env")) {
    Write-Host "Falta el archivo .env. Copiá .env.example como .env y completalo." -ForegroundColor Red
    exit 1
}

if (-not (Test-Path "vendor")) {
    Write-Host "Instalando dependencias con Composer..."
    composer install
}

Write-Host "Energhost en http://localhost:8000  (Ctrl+C para cortar)" -ForegroundColor Green
php -S localhost:8000 -t public public/router.php
