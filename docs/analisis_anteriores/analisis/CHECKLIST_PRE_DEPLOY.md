# Análisis del Proyecto — Checklist Pre-Deploy

**Proyecto:** ERP DRC (Dirección de Registro Civil)
**Fecha de revisión:** 2026-08-17
**Estado:** A punto de subir a producción

Este documento condensa las conclusiones de los análisis:
`STACK.md`, `ARQUITECTURA_ESCALABILIDAD.md`, `EFICIENCIA_RENDIMIENTO.md` y
`SEGURIDAD_AUDITORIA.md`.

---

## Veredicto

Arquitectura **sólida** para un ERP municipal (réplica de lectura, cola de jobs,
auditoría, caché con fallback, buenas prácticas de SQL).
La seguridad tiene **4 bloqueos críticos y 4 de alto riesgo** que deben corregirse
o mitigarse antes del deploy.

---

## 🔴 BLOQUEANTES (hacer antes de subir)

- [x] **Eliminar o bloquear `docs/migration_*.php`** de la web — resuelto: `.htaccess`
      bloquea `docs/`, `core/`, `public/(exports|reports)/`, `(logs|cache)/` y `*.sql|bak|old`.
      ✅ (2026-08-18)
- [x] **CSRF**: reemplazar validación `!empty($_POST['csrf_token'])` por
      `Auth::validateCSRF()` — resuelto en los 11 `save.php` de módulos, `update_perfil.php`,
      `update_usuario.php` y `catalogos_handler.php` (token presente en todas las vistas). ✅
- [ ] **Clave de cifrado**: `.env` local con `ENCRYPTION_KEY` aleatoria ✅ (local);
      **pendiente:** quitar el fallback hardcodeado de `core/Encryption.php:25` o
      aceptar el riesgo residual en producción.
- [ ] **Cambiar contraseña del admin por defecto** `admin@drc.gob.mx / Admin123!`
      (`docs/database.sql:34`) — decisión de producción; el seed crea cuentas de prueba
      separadas (supervisor/operador) sin tocar la del admin.

## 🟠 ALTO (corregir lo antes posible)

- [ ] **Whitelist de `ORDER BY`** (`in_array($dir, ['asc','desc'])`) en los 8
      `data.php` vulnerables (peticiones, nacimientos, defunciones, inexistencias,
      foraneas, ciudadanos, auditoria_data, errores_data).
- [x] **`public/exports/`**: resuelto — descarga solo vía `public/api/download_export.php`
      (sesión + propietario/ADMIN + job `completed`); carpeta agregada a `.gitignore` y
      desindexada con `git rm --cached` (15 `.xlsx`). **Pendiente:** purgar el historial
      git (BFG/filter-branch) antes de publicar el repo.
- [x] **`validate.php`**: token firmado — el QR embebe `base64("TIPO_id")."firma"` y
      `validate.php` verifica con `Encryption::verifySignature()` (hash_equals). ✅
- [x] **Rate-limit al login** — `public/auth.php` usa `\Core\RateLimiter::check('login', 10, 300)`.
      ✅
- [ ] **Bypass `?cron_token=`** de `Auth::check()`: restringir a CLI o al menos rotar
      `CRON_SECRET` en producción (el `.env` local ya tiene uno aleatorio).
- [ ] **XSS almacenado** en `modules/reportes/data.php` (escapar antes de `json_encode`).

## 🟡 MEDIO (primera semana post-deploy)

- [x] Flags de cookie de sesión — `core/Auth.php` ya aplica `HttpOnly`, `SameSite=Lax` y
      `Secure` bajo HTTPS (verificado 2026-08-18).
- [x] Escapar atributos `data-*` de `public/usuarios.php`; whitelist de valores de `rol`
      (ADMIN/SUPERVISOR/OPERADOR en `update_usuario.php`). ✅
- [x] Reemplazar `$e->getMessage()` de las respuestas JSON por mensaje genérico —
      aplicado en stats, notifications, Gestores, 6 `data.php`, `get_details.php`,
      `update_status.php`, `delete.php`, `catalogos_handler.php`, `pdf.php`
      (detalle a `error_log`). ✅
- [x] Aplicar índices — `docs/migration_softdelete_indices.php` (idempotente) agrega
      índices complementarios + columnas soft-delete; **pendiente:** ejecutarla en BD
      (local y producción).
- [x] Bloquear `docs/`, `logs/`, `cache/` y `*.sql` en `.htaccess` de producción. ✅
- [ ] Configurar cron del worker (`* * * * * php core/Worker.php`).
- [ ] OPcache activado; `display_errors=Off`, `log_errors=On` en producción.

## ❌ NO HACER como está hoy

- No dejar los exportadores síncronos vía `popen("start /B ...")` como mecanismo
  principal (ruta hardcodeada Windows, abuso de recursos) — delegar a la cola.
- No subir `cache/` con `0777` ni dejarla accesible desde la web.
- No usar el `.env.example` tal cual (claves predecibles).

---

## Resumen por área

| Área | Calificación | Nota |
|---|---|---|
| Stack | ✅ Adecuado | PHP puro sin framework; dependencias mínimas |
| Escalabilidad | 🔶 Aceptable | Server-side + cola + réplica; vertical, no horizontal |
| Eficiencia | 🔶 Buena | Faltan índices; errores crudos en JSON; worker portable |
| Seguridad | 🔴 Bloqueada | Base sólida, pero 4 críticos y 4 altos pendientes |

## Entregable 1.4.0 (ventanilla, turnos, exportación restringida)

- [x] **Ejecutar la migración** `php docs/migration_turnos_ventanilla.php` en la BD local
      (rol `COORDINADOR`, banderas, tablas `peticiones_ventanilla`/`turnos`,
      catálogo `tipo_peticion_ventanilla`, prefijos `VP-`/`VT-`); ejecutar también en
      producción. El script ya está corregido (inserta en `catalogos` solo columnas
      existentes y consulta `configuracion` por `clave`). ✅ (2026-08-18)
- [x] Código del módulo **Petición Rápida** (`modules/peticion_rapida`) listo
      (index/create/save/data/estado/ticket, CSRF + auditoría + folios `VP-`).
- [x] Código del **sistema de turnos** listo (`modules/turnos/*` + `public/turnos.php` +
      `public/api/turnos_pantalla.php`).
- [x] **Exportación restringida**: `Auth::checkExport()` en los 14 endpoints + botones
      ocultos sin `canExportar()` + registro `Exportaciones/EXPORTAR` en auditoría.
- [x] Rol **COORDINADOR** y flags de ventanilla/exportación en `usuarios.php` y
      `update_usuario.php` (whitelist + defaults por rol).
- [x] **Errores legibles**: `Core\Services\ErrorMessages` + `Auditoria::logError()` con
      mensaje humano / técnico; filtros de módulo/acción en el panel de auditoría.
- [x] Probar en vivo: turno (crear → atender → finalizar, transición inválida
      rechazada) ✅, petición rápida (crear → entregar, ticket, CSRF) ✅,
      exportar como OPERADOR sin flag (403 con mensaje claro) ✅ y como ADMIN
      (job + auditoría `Exportaciones/EXPORTAR`) ✅; filtros módulo/acción en
      auditoría ✅; pantalla pública de turnos ✅. (2026-08-18)

## Archivos relacionados

- `docs/analisis/STACK.md`
- `docs/analisis/ARQUITECTURA_ESCALABILIDAD.md`
- `docs/analisis/EFICIENCIA_RENDIMIENTO.md`
- `docs/analisis/SEGURIDAD_AUDITORIA.md`