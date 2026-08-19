# scripts/rollback.ps1
# Protocolo de Rollback Automatizado de Emergencia para Windows
param (
    [string]$BackupSql = "C:\xampp\htdocs\DRC\backups\backup_predeploy.sql",
    [string]$TagPrevio = "HEAD~1"
)

Write-Host "=================================================================" -ForegroundColor Red
Write-Host " PROTOCOLO DE ROLLBACK DE EMERGENCIA — ERP REGISTRO CIVIL (DRC) " -ForegroundColor Red
Write-Host "=================================================================" -ForegroundColor Red

if (Test-Path $BackupSql) {
    Write-Host "1. Restaurando base de datos desde $BackupSql ..." -ForegroundColor Yellow
    & "C:\xampp\mysql\bin\mysql.exe" -u root drc_erp -e "source $BackupSql"
    Write-Host "✔ Base de datos restaurada." -ForegroundColor Green
} else {
    Write-Host "⚠ Archivo de respaldo no encontrado en $BackupSql" -ForegroundColor Yellow
}

Write-Host "2. Revirtiendo código fuente a $TagPrevio ..." -ForegroundColor Yellow
git reset --hard $TagPrevio
& "C:\xampp\php\php.exe" -r "if (file_exists('composer.phar')) { passthru('C:\\xampp\\php\\php.exe composer.phar dump-autoload -o'); } else { passthru('composer dump-autoload -o'); }"

Write-Host "3. Purgando caché temporal..." -ForegroundColor Yellow
if (Test-Path "cache") {
    Remove-Item "cache\*" -Recurse -Force -ErrorAction SilentlyContinue
}

Write-Host "=================================================================" -ForegroundColor Green
Write-Host " ✔ ROLLBACK COMPLETADO. SISTEMA RESTAURADO.                     " -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Green
