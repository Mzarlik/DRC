# 🔵 PRIORIDAD BAJA — Mejoras Futuras y Roadmap

## 33. CI/CD con GitHub Actions

Pipeline con: PSR-12 (PHP CS Fixer), PHPStan análisis estático, PHPUnit, Playwright E2E.

---

## 34. Firma electrónica avanzada en actas

Emisión con firma electrónica simplificada, marcas de agua digitales, QR de verificación mejorados.

---

## 35. Observabilidad y telemetría

Integración de Sentry o Monolog estructurado para monitoreo en caliente.

---

## 36. Portabilidad — Ruta PHP hardcodeada

**Archivos:** [core/Jobs.php](file:///c:/xampp/htdocs/DRC/core/Jobs.php) L28, [core/Services/FirmaElectronicaService.php](file:///c:/xampp/htdocs/DRC/core/Services/FirmaElectronicaService.php) L73-77

**Acción:** Usar constante `PHP_BINARY` o leer desde `.env`.

---

## 37. Credenciales fallback en Database.php

**Archivo:** [core/Database.php](file:///c:/xampp/htdocs/DRC/core/Database.php) L17-21

**Acción:** Forzar error fatal si credenciales no están en `.env`.

---

## 38. Expandir suite de pruebas

**Estado actual:** 8 tests unitarios + 1 test E2E.

**Sugerencias:**
- Tests para `PeticionRapidaService`, `ExcelReportFormatter`, `ErrorMessages`
- Tests de integración para flujos CRUD
- Tests E2E para ciudadano, petición rápida, turnos
- Code coverage con `--coverage-html`

---

## 39. Localizar todas las dependencias CDN

Verificar que **todas** las dependencias pueden funcionar 100% offline/airgapped (Google Fonts ya identificado en #10).

---

## 40. Rutas OpenSSL hardcodeadas

**Archivo:** [core/Services/FirmaElectronicaService.php](file:///c:/xampp/htdocs/DRC/core/Services/FirmaElectronicaService.php) L73-77

**Problema:** Rutas estáticas para `openssl.cnf` (`C:/xampp/apache/bin/openssl.cnf`, `/etc/ssl/openssl.cnf`).

**Acción:** Configurar en `.env` o detectar automáticamente.

---

## 41. Agregar Content-Security-Policy header

**Archivo:** [.htaccess](file:///c:/xampp/htdocs/DRC/.htaccess)

**Acción:** Agregar cabecera CSP para mitigar XSS y data exfiltration.

---

## 42. Considerar migrar `usuarios.php` a DataTables

Ya mencionado en #20 — listado de usuarios es el único sin DataTables server-side.
