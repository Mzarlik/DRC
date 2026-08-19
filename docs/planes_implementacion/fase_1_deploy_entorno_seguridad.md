# Plan de Implementación — Fase 1: Preparación del Entorno, Seguridad Perimetral y Gestión de Secretos

Este plan describe los pasos exactos para ejecutar la **Fase 1** del primer despliegue del ERP DRC, estableciendo las bases de infraestructura, aislamiento de secretos criptográficos, hardening perimetral y verificación de extensiones de PHP 8.2+.

---

## User Review Required

> [!IMPORTANT]
> **Generación de Secretos Criptográficos (`.env`):**
> Se generarán claves de alta entropía (64 caracteres hexadecimales / 256 bits) para `ENCRYPTION_KEY`, `BLIND_INDEX_KEY` y `CRON_SECRET`.
> Si ya existen datos previamente cifrados en la base de datos local con la clave por defecto, cambiar la `ENCRYPTION_KEY` requerirá recifrar esos registros (o mantener la clave existente para desarrollo). En este plan, actualizaremos `.env.example` y configuraremos el soporte para `BLIND_INDEX_KEY` en `Core\Encryption`.

> [!NOTE]
> **Aislamiento en `.htaccess`:**
> Se actualizará `.htaccess` para bloquear de forma exhaustiva los nuevos directorios y archivos de despliegue (`docs/`, `logs/`, `cache/`, `schema_migrations`, planes de implementación).

---

## Proposed Changes

### 1. Infraestructura y Variables de Entorno

#### [MODIFY] [.env.example](file:///C:/xampp/htdocs/DRC/.env.example)
- Incorporar variables estándar para `BLIND_INDEX_KEY`, `CACHE_DRIVER`, `REDIS_SOCKET`, `APP_ENV`, `APP_DEBUG` y `PHP_BIN`.

#### [NEW] [scripts/generate_keys.php](file:///C:/xampp/htdocs/DRC/scripts/generate_keys.php)
- Script CLI seguro para generar claves criptográficas aleatorias para `.env` en cualquier sistema operativo.

---

### 2. Seguridad Perimetral y Reglas del Servidor Web

#### [MODIFY] [.htaccess](file:///C:/xampp/htdocs/DRC/.htaccess)
- Endurecer las reglas para bloquear acceso directo a:
  - Archivos `.env*`, `.git*`, `composer.*`, `package*.json`.
  - Directorios `core/`, `docs/`, `logs/`, `cache/`, `.agents/`.
  - Archivos `.sql`, `.bak`, `.enc`.
- Agregar cabeceras de seguridad HTTP por defecto (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`).

---

### 3. Core & Runtime PHP 8.2+

#### [MODIFY] [core/Encryption.php](file:///C:/xampp/htdocs/DRC/core/Encryption.php)
- Añadir soporte para `BLIND_INDEX_KEY` con método `getBlindIndex(string $plaintext): ?string` para preparar la arquitectura de Blind Index de la Fase 3.
- Mantener compatibilidad total hacia atrás con `decrypt()` y `encrypt()`.

#### [MODIFY] [core/Auth.php](file:///C:/xampp/htdocs/DRC/core/Auth.php)
- Asegurar que `initSession()` aplique directivas estrictas de cookies (`httponly = true`, `samesite = Lax`, `use_strict_mode = 1`).

#### [NEW] [scripts/check_environment.php](file:///C:/xampp/htdocs/DRC/scripts/check_environment.php)
- Script automatizado para validar la versión de PHP (>= 8.2), verificar que las 10 extensiones críticas (`pdo_mysql`, `openssl`, `mbstring`, `gd`, `zip`, `curl`, `json`, `fileinfo`, `zlib`, `opcache`) estén activas y confirmar permisos de escritura en `cache/`, `logs/` y `public/exports/`.

---

## Verification Plan

### Automated Tests
- Ejecutar verificación de entorno:
  ```bash
  C:\xampp\php\php.exe scripts/check_environment.php
  ```
- Ejecutar suite de pruebas unitarias de PHPUnit:
  ```bash
  C:\xampp\php\php.exe vendor/bin/phpunit
  ```
- Regenerar mapa de clases optimizado:
  ```bash
  C:\xampp\php\php.exe composer.phar dump-autoload -o
  ```

### Manual Verification
1. Probar la generación de llaves seguras:
   ```bash
   C:\xampp\php\php.exe scripts/generate_keys.php
   ```
2. Verificar bloqueo de archivos sensibles intentando acceder vía navegador/curl a:
   - `/.env` -> Debe retornar `403 Forbidden`
   - `/core/Database.php` -> Debe retornar `403 Forbidden`
   - `/docs/esquema_bd.md` -> Debe retornar `403 Forbidden`
