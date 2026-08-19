# Plan de Implementación — Fase 4: Procesamiento Asíncrono, Reportes, Generador PDF Unicode (DejaVu Sans), e.firma y Mantenimiento Operativo

Este plan describe los pasos exactos para ejecutar la **Fase 4** del despliegue del ERP DRC, estableciendo la resiliencia del Worker CLI (reconexión automática ante `wait_timeout`), modernizando la emisión de Actas en PDF con fuentes Unicode y código QR firmado con HMAC, integrando el servicio de **Firma Electrónica Avanzada (PKI / X.509)** y calendarizando el mantenimiento de archivos temporales y rotación de logs.

---

## User Review Required

> [!IMPORTANT]
> **Resiliencia del Worker CLI (`core/Worker.php`):**
> Se integrará `getActivePdo()` para realizar ping antes de procesar lotes y reconectar automáticamente si la conexión MySQL expiró por inactividad nocturna (`Error 2006 MySQL server has gone away`).
> Todos los archivos `.xlsx` exportados recibirán permisos `0664` (`umask 0027`) para garantizar su descarga web.

> [!NOTE]
> **Generación de Actas PDF con Fuentes Unicode (`DejaVu Sans`):**
> Se creará `Core\Services\PdfGenerator` con soporte completo UTF-8 para grafías indígenas (saltillos, acentos, diéresis) y sellado con código QR firmado con HMAC-SHA256 validable en `public/validate.php`.

---

## Proposed Changes

### 1. Resiliencia del Worker y Procesamiento Asíncrono

#### [MODIFY] [core/Worker.php](file:///C:/xampp/htdocs/DRC/core/Worker.php)
- Implementar función `getActivePdo()` con ping activo (`SELECT 1`).
- Aplicar `chmod($fullPath, 0664)` en archivos exportados.
- Optimizar el loop de procesamiento continuo.

---

### 2. Generador de Actas Oficiales y Firma Digital

#### [NEW] [core/Services/PdfGenerator.php](file:///C:/xampp/htdocs/DRC/core/Services/PdfGenerator.php)
- Servicio centralizado para generación de actas (Nacimiento, Matrimonio, Defunción, Inexistencias) con:
  - Soporte Unicode total con fuente `DejaVu Sans`.
  - Generación de código QR (Chillerlan) firmado con HMAC-SHA256 (`Core\Encryption::sign()`).
  - Metadatos institucionales y marca de agua oficial.

#### [NEW] [core/Services/FirmaElectronicaService.php](file:///C:/xampp/htdocs/DRC/core/Services/FirmaElectronicaService.php)
- Servicio de Firma Electrónica Avanzada (FIEL / e.firma) usando criptografía asimétrica RSA-SHA256 con certificados `.cer` y llave `.key` vía `openssl_sign()` y `openssl_verify()`.

---

### 3. Mantenimiento y Automatización Operativa

#### [NEW] [scripts/cleanup_exports.php](file:///C:/xampp/htdocs/DRC/scripts/cleanup_exports.php)
- Script de purga multiplataforma que elimina reportes `.xlsx` (> 48 horas) y `.pdf` temporales (> 7 días).

#### [NEW] [scripts/logrotate_drc_worker.conf](file:///C:/xampp/htdocs/DRC/scripts/logrotate_drc_worker.conf)
- Plantilla de configuración para `/etc/logrotate.d/drc-worker` con compresión diaria y retención de 14 días.

---

### 4. Pruebas Unitarias de Verificación

#### [NEW] [tests/Unit/FirmaElectronicaTest.php](file:///C:/xampp/htdocs/DRC/tests/Unit/FirmaElectronicaTest.php)
- Pruebas unitarias para validar la firma y verificación asimétrica con pares de llaves OpenSSL.

#### [NEW] [tests/Unit/PdfGeneratorTest.php](file:///C:/xampp/htdocs/DRC/tests/Unit/PdfGeneratorTest.php)
- Pruebas unitarias para la generación de actas en PDF y QR firmado con HMAC.

---

## Verification Plan

### Automated Tests
- Ejecutar suite completa de pruebas unitarias:
  ```bash
  C:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/FirmaElectronicaTest.php tests/Unit/PdfGeneratorTest.php tests/Unit/DeadlockRetryTest.php tests/Unit/EncryptionTest.php tests/Unit/UtilsTest.php
  ```
- Probar script de purga:
  ```bash
  C:\xampp\php\php.exe scripts/cleanup_exports.php
  ```

### Manual Verification
1. Generar un Acta en PDF para un ciudadano con caracteres especiales (ej. *Xóchitl Ta'an K'an*) y comprobar que los saltillos y acentos se visualicen perfectamente.
2. Escanear el código QR emitido y comprobar su autenticidad en `public/validate.php`.
