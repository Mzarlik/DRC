# Plan de Implementación — Fase 5: Testing Automatizado, 14 Smoke Tests, Respaldos Air-Gapped y Plan de Rollback

Este plan describe los pasos exactos para ejecutar la **Fase 5** del despliegue del ERP DRC, estableciendo la verificación automatizada integral con **PHPUnit**, la suite de **14 Smoke Tests Pre-Deploy**, la herramienta de **respaldos desconectados (Air-Gapped / Anti-Ransomware)** con cifrado AES-256 y los scripts de **Rollback automatizado** para contingencias en producción.

---

## User Review Required

> [!IMPORTANT]
> **Estrategia de Respaldos Air-Gapped contra Ransomware:**
> Se implementarán scripts de respaldo cifrado multiplataforma (`scripts/backup_airgapped.sh` y `scripts/backup_airgapped.ps1`), los cuales generan volcados SQL de MySQL, los cifran con AES-256 (`.sql.enc`) y los preparan para sincronización a medios desconectados o almacenamiento inmutable (*Cold Storage*).

> [!NOTE]
> **14 Smoke Tests Automatizados (`scripts/run_smoke_tests.php`):**
> Se creará un ejecutor CLI automatizado para validar los 14 puntos de control de producción (seguridad perimetral, CSRF, Blind Index, auditoría LGPDPPSO, grafías indígenas, QR HMAC, permisos y assets offline).

---

## Proposed Changes

### 1. Verificación Automatizada y Smoke Tests

#### [NEW] [scripts/run_smoke_tests.php](file:///C:/xampp/htdocs/DRC/scripts/run_smoke_tests.php)
- Script de validación automatizada de los 14 puntos de control pre-deploy con reporte visual de aprobaciones.

---

### 2. Respaldos Cifrados Fuera de Sitio (Air-Gapped / Cold Storage)

#### [NEW] [scripts/backup_airgapped.sh](file:///C:/xampp/htdocs/DRC/scripts/backup_airgapped.sh)
- Script Bash para Linux con volcado `mysqldump`, cifrado `openssl enc -aes-256-cbc` y sincronización a medio aislado.

#### [NEW] [scripts/backup_airgapped.ps1](file:///C:/xampp/htdocs/DRC/scripts/backup_airgapped.ps1)
- Script PowerShell para Windows con volcado transaccional y cifrado AES-256.

---

### 3. Plan de Contingencia y Rollback Automatizado

#### [NEW] [scripts/rollback.sh](file:///C:/xampp/htdocs/DRC/scripts/rollback.sh)
- Script de restauración de emergencia para Linux (reversión de código Git, restauración de BD, purga de caché y reinicio de servicios en < 3 min).

#### [NEW] [scripts/rollback.ps1](file:///C:/xampp/htdocs/DRC/scripts/rollback.ps1)
- Script de restauración de emergencia para Windows.

---

## Verification Plan

### Automated Tests
- Ejecutar la suite completa de PHPUnit:
  ```bash
  C:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/FirmaElectronicaTest.php tests/Unit/PdfGeneratorTest.php tests/Unit/DeadlockRetryTest.php tests/Unit/EncryptionTest.php tests/Unit/UtilsTest.php
  ```
- Ejecutar los 14 Smoke Tests automatizados:
  ```bash
  C:\xampp\php\php.exe scripts/run_smoke_tests.php
  ```

### Manual Verification
1. Probar la generación de un respaldo cifrado con `scripts/backup_airgapped.ps1` o `scripts/backup_airgapped.sh`.
2. Validar que la restauración de respaldo se ejecute limpiamente.
