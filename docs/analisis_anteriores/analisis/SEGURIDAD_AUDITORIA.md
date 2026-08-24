# Análisis del Proyecto — Seguridad y Auditoría

**Proyecto:** ERP DRC (Dirección de Registro Civil)
**Fecha de revisión:** 2026-08-17
**Estado:** Pre-deploy

Auditoría estática manual sobre `modules/`, `public/`, `core/`, `docs/` y raíz
(excluido `vendor/`). Severidad: CRÍTICA / ALTA / MEDIA / BAJA.

---

## 1. LO QUE ESTÁ BIEN IMPLEMENTADO ✅

- **Prepared statements reales** (`EMULATE_PREPARES=false`) en el ~95% de las
  consultas: `core/Database.php:60`, todos los `save.php`, `GestorNacimientos.php:37-48`,
  `GestorInexistencias.php:68-80`, `CronReport.php`, `Catalogo.php`, `Worker.php`.
- **Contraseñas**: `password_hash(PASSWORD_BCRYPT)` + `password_verify`
  (`auth.php:22`, `update_usuario.php:45`, `update_perfil.php:73-79`).
- **Anti-session-fixation**: `session_regenerate_id(true)` en login y cambio de credenciales.
- **CSRF correcto** (tokens de 256 bits + comparación estricta) en los endpoints
  más delicados: `peticiones/update_status.php:19`, `ciudadanos/delete.php:12`.
- **Rate limiter** por IP: `core/RateLimiter.php` (usado en `ciudadanos/search.php`).
- **Escapado de salida** `htmlspecialchars(ENT_QUOTES, 'UTF-8')` en todos los feeds
  DataTables salvo `reportes/data.php` (ver §3.2).
- **No hay subida de archivos** en toda la aplicación (riesgo N/A).
- **No hay `eval`/`shell_exec`/`exec`** en código de aplicación (solo en vendor/).
- **Cifrado AES-256-CBC** de CURP con búsqueda determinista.
- **Auditoría**: `Auditoria::logAccion` en todas las mutaciones; manejadores globales
  de error/excepción que registran en BD sin exponer.
- **Guardas CLI-only** en `Worker.php:5` y `CronReport.php:5`.
- **`.htaccess` raíz**: bloquea `.env`, composer, `.gitignore`, `core/` y activa
  `Options -Indexes`.
- **`.env` en `.gitignore`** y credenciales desacopladas del código.

---

## 2. INYECCIÓN SQL

### 2.1 CRÍTICA — `ORDER BY` con input sin validar (ALTA)

`$columnSortOrder` viene directo de `$_GET['order'][0]['dir']` y se interpola en el SQL:

| Archivo | Líneas |
|---|---|
| `modules/peticiones/data.php` | 30, 52 |
| `modules/nacimientos/data.php` | 29, 52 |
| `modules/defunciones/data.php` | 30, 53 |
| `modules/inexistencias/data.php` | 36, 72 |
| `modules/foraneas/data.php` | 31, 53 |
| `modules/ciudadanos/data.php` | 42, 68 |
| `public/api/auditoria_data.php` | 34, 53 |
| `public/api/errores_data.php` | 34, 53 |

Con `EMULATE_PREPARES=false` no hay multi-statement, pero `ORDER BY` admite
subconsultas → extracción ciega (boolean/time-based) de toda la BD, incluido
`usuarios.password_hash`.

**Sí lo validan** (referencia correcta, `in_array(..., ['asc','desc'])`):
reconocimientos, divorcios, matrimonios, inscripciones, curp, actas_locales.

### 2.2 BAJA — Interpolación de valores generados

- `public/api/stats.php:17-25,84-92`: interpolan `$today`/`$six_days_ago`
  (fechas de `date()`, no inyectables).
- `modules/reportes/data.php:182`: interpola `$length`/`$start` con `(int)` — seguro.

---

## 3. XSS

### 3.1 ALTA — XSS almacenado en Reportes

- `modules/reportes/data.php:188-193`: devuelve `$data` (nombres de ciudadanos,
  observaciones) **sin escapar**; se renderiza con `innerHTML`
  (`modules/reportes/index.php:359-363`). Un nombre tipo `<img src=x onerror=...>`
  ejecuta en la sesión de cualquier usuario con permiso de Reportes.

### 3.2 MEDIA — Atributos sin escapar en usuarios

- `public/usuarios.php:265`: `data-rol="<?php echo $u['rol']; ?>"` — el rol es
  cadena libre escrita por admin (`update_usuario.php:29`).
- `public/usuarios.php:252-253`: clase CSS construida con `$u['rol']` sin escapar.

### 3.3 MEDIA — Mensajes de excepción reflejados

- `modules/curp/data.php:81`, `modules/actas_locales/data.php:182`,
  `modules/reportes/data.php:201`, `public/api/stats.php:140`,
  `public/api/notifications.php:207`, `get_details.php:93`, `export_*.php`
  (ej. `nacimientos/export_excel.php:40`): devuelven `$e->getMessage()` en JSON.

### 3.4 BAJA

- `public/api/notifications.php:102`: `$desc` HTML construido con `file_path` de BD.
- `modules/actas_locales/index.php:488-523`: template literal `${data.p_nombre}` —
  seguro hoy porque `get_details.php:86` escapa; dependencia frágil.

---

## 4. CSRF

Implementación fragmentaria. Solo 2 endpoints validan bien. **La mayoría de los
formularios POST no tienen protección real.**

### 4.1 CRÍTICA — Cambio de credenciales y creación de usuarios sin CSRF

| Archivo | Riesgo |
|---|---|
| `public/update_perfil.php:20` | Cambiar correo/contraseña → **toma de cuenta completa** |
| `public/update_usuario.php:20` | Crear usuarios / cambiar rol → **crear un ADMIN vía CSRF** |

### 4.2 ALTA — Token CSRF falso (solo `!empty()`)

Cualquier token **no vacío** pasa; = sin protección:

- `modules/ciudadanos/save.php:12`
- `modules/nacimientos/save.php:13`
- `modules/peticiones/save.php:12`
- `modules/foraneas/save.php:12`
- `modules/inexistencias/save.php:14`
- `modules/defunciones/save.php:13`

### 4.3 ALTA — Sin token en absoluto

- `modules/curp/save.php:9`, `divorcios/save.php:9`, `inscripciones/save.php:9`,
  `matrimonios/save.php:9`, `reconocimientos/save.php:9`
- `public/api/catalogos_handler.php:29,59` (`agregar_opcion`, `toggle_estado`)

### 4.4 MEDIA — GET con trabajo pesado sin token ni rate-limit

Todos los exportadores (`modules/*/export_excel.php:14,29-31` y
`public/api/export_{auditoria,usuarios,errores}.php:33`) son GET públicos
(con sesión) → CSRF→DoS / abuso de jobs.

### 4.5 BAJA — Login sin CSRF

`public/login.php:87-105` → `auth.php` (login CSRF / fijación de estado).

---

## 5. AUTORIZACIÓN

### 5.1 CRÍTICA — Migraciones web sin autenticación

| Archivo | Líneas | Riesgo |
|---|---|---|
| `docs/migration_extra.php` | 10-36 | `ALTER TABLE` / `CREATE INDEX` vía web |
| `docs/migration_queue_reportes.php` | 10-31 | Crea tablas e índices |
| `docs/migration_encrypt.php` | 13-42 | Lee y **re-encripta TODAS las CURP** |

No están bloqueados por `.htaccess` (solo `core/` lo está). Deben eliminarse del
servidor o bloquearse.

### 5.2 ALTA — Bypass universal por `cron_token`

`core/Auth.php:21`: cualquier página que llame `Auth::check()` se salta con
`?cron_token=<CRON_SECRET>` del `.env`. Es una llave maestra de autenticación;
si el `.env` se filtra (backup, log, `.env.example` con el mismo valor inicial),
**todo el sistema queda expuesto**.

### 5.3 ALTA — `validate.php` fuga PII sin autenticación

`public/validate.php:5-75`: endpoint público que revela nombre completo + número
de acta de cualquier registro con un token **base64 no firmado**
(`base64("NACIMIENTO_12")`), enumerable e infinito.

### 5.4 OK — resto de la cobertura

`checkPermission('permiso_*')` en todos los `save.php`/`data.php`/`get_details.php`/
`pdf.php`; rol ADMIN/SUPERVISOR en `update_status.php:6`, `catalogos_handler.php:9`,
`auditoria.php:5`, `catalogos.php:7`, `auditoria_data.php:5`.

---

## 6. SESIONES

| Hallazgo | Archivo | Severidad |
|---|---|---|
| Sin `session_set_cookie_params` / `session.cookie_httponly` / `secure` / `samesite` en ninguna parte → cookie legible por JS (robo por XSS), sin SameSite, sin Secure en HTTPS | toda la app (`auth.php:28` inicia sesión) | MEDIA |
| Logout no invalida la cookie del cliente | `public/logout.php:3-6` | BAJA |
| Sin timeout de inactividad / expiración de sesión | — | BAJA |
| ✅ `session_regenerate_id(true)` en login y cambios de credenciales | `auth.php:29`, `update_perfil.php:86`, `update_usuario.php:141` | — |
| ✅ `session_unset()` + `session_destroy()` en logout | `logout.php:4-5` | — |

---

## 7. OTROS RIESGOS

| Hallazgo | Archivo:Línea | Severidad |
|---|---|---|
| **Clave AES-256 fallback hardcodeada** (`'drc_system_secure_aes256_key_2026'`): si falta `ENCRYPTION_KEY`, todas las CURP se descifran con clave pública conocida | `core/Encryption.php:25` | **CRÍTICA** |
| **15 `.xlsx` con PII commiteados en git** y carpeta `public/exports/` descargable sin login con URL predecible (retención 48 h) | git history; `core/Worker.php:31-34,185-192` | **ALTA** |
| Login **sin rate-limiting** (`RateLimiter` solo en `ciudadanos/search.php:15`) → fuerza bruta directa | `public/auth.php:7-53` | **ALTA** |
| Credenciales hardcodeadas `root`/pass vacío ("Cambiar en producción") | `core/Database.php:16-19` | MEDIA |
| `unserialize()` de archivos `.cache` en directorio web-accesible creado con `0777` | `core/Cache.php:60,76,81,89` | MEDIA |
| Formula injection en Excel (celdas con texto libre que inicia con `=`, `+`, `-`) | `core/Worker.php:165-176` y 11 exportadores | MEDIA |
| `rol` de usuario sin whitelist de valores (alimenta el XSS de `usuarios.php:265`) | `update_usuario.php:29` | MEDIA |
| Comparación de `cron_token` no constante-tiempo | `core/Auth.php:21` | BAJA |
| `errorHandler` retorna `false` → PHP sigue manejando el error; si `display_errors=On` en producción, se muestra al usuario | `core/Auditoria.php:67-78` | BAJA |
| Sin `X-Frame-Options` / CSP / `nosniff` (clickjacking) | todos los módulos | BAJA |
| `.htaccess` no protege `docs/`, `logs/`, `cache/`, `database_auditoria.sql`, `public/exports/`; depende de Apache+AllowOverride (en Nginx/IIS el `.env` queda expuesto) | `.htaccess` raíz | MEDIA |
| Admin por defecto `admin@drc.gob.mx / Admin123!` documentado en el seed SQL | `docs/database.sql:34` | ALTA (cambiar antes del deploy) |

---

## 8. PRIORIDAD DE REMEDIACIÓN

1. **CRÍTICO** — bloque/eliminar `docs/migration_*.php` de la web
2. **CRÍTICO** — quitar clave fallback de `Encryption.php`; generar `ENCRYPTION_KEY` aleatoria
3. **CRÍTICO** — CSRF real (`Auth::validateCSRF`) en `update_perfil.php`, `update_usuario.php` y los 6 `save.php` con `!empty()`; añadir a los 5 sin token
4. **ALTO** — whitelist de `$columnSortOrder` en los 8 `data.php`
5. **ALTO** — mover `exports/` fuera del docroot o exigir sesión+token para descargar; purgar `.xlsx` del historial git
6. **ALTO** — firmar/aleatorizar el token de `validate.php`
7. **ALTO** — rate-limit en login
8. **ALTO** — revisar/eliminar el bypass `cron_token` (o restringirlo a CLI)
9. **MEDIO** — escapar `reportes/data.php` y atributos de `usuarios.php`; flags de cookie de sesión; sanitizar `$e->getMessage()` en JSON; whitelist de `rol`; endurecer cookies
10. **BAJO** — CSP/X-Frame-Options, timeout de sesión, purga de auditoría