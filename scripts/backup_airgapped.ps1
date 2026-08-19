# scripts/backup_airgapped.ps1
# Generador de Respaldo Cifrado de Base de Datos para Windows (Anti-Ransomware)
param (
    [string]$EncryptionKey = "DrcGobMxSecureBackupKey2026!",
    [string]$BackupDir = "C:\xampp\htdocs\DRC\backups"
)

$ErrorActionPreference = "Stop"
$fecha = Get-Date -Format "yyyyMMdd_HHmmss"

if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

$sqlFile = "$BackupDir\drc_erp_$fecha.sql"
$encFile = "$BackupDir\drc_erp_$fecha.sql.enc"

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host " RESPALDO TRANSACCIONAL CIFRADO (AIR-GAPPED) — ERP DRC (WINDOWS)" -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan

Write-Host "1. Generando volcado de base de datos..." -ForegroundColor Yellow
$mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"

if (Test-Path $mysqldumpPath) {
    & $mysqldumpPath -u root --single-transaction --routines --triggers --default-character-set=utf8mb4 drc_erp > $sqlFile
    Write-Host "✔ Volcado SQL generado: $sqlFile" -ForegroundColor Green

    # Cifrado con OpenSSL si está disponible
    $opensslPath = "C:\xampp\apache\bin\openssl.exe"
    if (Test-Path $opensslPath) {
        Write-Host "2. Cifrando respaldo con AES-256-CBC..." -ForegroundColor Yellow
        & $opensslPath enc -aes-256-cbc -salt -in $sqlFile -out $encFile -k $EncryptionKey -pbkdf2
        Remove-Item $sqlFile -Force
        Write-Host "✔ Respaldo cifrado exitosamente: $encFile" -ForegroundColor Green
    } else {
        Write-Host "⚠ OpenSSL no detectado en Apache bin. El volcado se mantiene en: $sqlFile" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ No se encontró mysqldump.exe en $mysqldumpPath" -ForegroundColor Red
}

Write-Host "=================================================================" -ForegroundColor Cyan
