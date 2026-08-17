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

- [ ] **Eliminar o bloquear `docs/migration_*.php`** de la web (ejecutan ALTER TABLE
      y re-encriptan CURP sin autenticación).
- [ ] **CSRF**: reemplazar validación `!empty($_POST['csrf_token'])` por
      `Auth::validateCSRF()` en los 6 `save.php` (ciudadanos, nacimientos, peticiones,
      foraneas, inexistencias, defunciones); añadir token a los 5 sin token
      (curp, divorcios, inscripciones, matrimonios, reconocimientos) y a
      `public/update_perfil.php` y `public/update_usuario.php` (toma de cuenta/crear admin).
- [ ] **Clave de cifrado**: quitar el fallback hardcodeado de `core/Encryption.php:25`
      y generar `ENCRYPTION_KEY` aleatoria (no la del `.env.example`).
- [ ] **Cambiar contraseña del admin por defecto** `admin@drc.gob.mx / Admin123!`
      (`docs/database.sql:34`).

## 🟠 ALTO (corregir lo antes posible)

- [ ] **Whitelist de `ORDER BY`** (`in_array($dir, ['asc','desc'])`) en los 8
      `data.php` vulnerables (peticiones, nacimientos, defunciones, inexistencias,
      foraneas, ciudadanos, auditoria_data, errores_data).
- [ ] **`public/exports/`**: mover fuera del docroot o exigir sesión + token.
      **Purgar los 15 `.xlsx` del historial git** (BFG/filter-branch) y añadir la
      carpeta a `.gitignore`.
- [ ] **`validate.php`**: firmar/aleatorizar el token (no base64 de un ID predecible).
- [ ] **Rate-limit al login** (`public/auth.php`) reutilizando `Core\RateLimiter`.
- [ ] **Bypass `?cron_token=`** de `Auth::check()`: restringir a CLI (el worker ya
      es CLI-only) o al menos rotar `CRON_SECRET` y no dejarlo en `.env.example`.
- [ ] **XSS almacenado** en `modules/reportes/data.php` (escapar antes de `json_encode`).

## 🟡 MEDIO (primera semana post-deploy)

- [ ] Flags de cookie de sesión: `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS
      (vía `session_set_cookie_params` o `php.ini`).
- [ ] Escapar atributos `data-*` de `public/usuarios.php`; whitelist de valores de `rol`.
- [ ] Reemplazar `$e->getMessage()` de las respuestas JSON por mensaje genérico
      (la excepción ya queda en `error_logs`).
- [ ] Aplicar `docs/migration_extra.php` (índices) y extender índices a las demás tablas.
- [ ] Bloquear `docs/`, `logs/`, `cache/` y `*.sql` en `.htaccess` de producción.
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

## Archivos relacionados

- `docs/analisis/STACK.md`
- `docs/analisis/ARQUITECTURA_ESCALABILIDAD.md`
- `docs/analisis/EFICIENCIA_RENDIMIENTO.md`
- `docs/analisis/SEGURIDAD_AUDITORIA.md`