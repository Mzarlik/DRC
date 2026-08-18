# Referencia de API — ERP DRC

Endpoints internos (JSON) consumidos por el frontend. Todos los endpoints sensibles requieren sesión activa vía `\Core\Auth::check()`.

**Convenciones generales:**

- Base URL: `http://localhost/DRC/` (ajustar según despliegue).
- Los endpoints `data.php` de módulos responden en formato **DataTables legacy**: `{draw, iTotalRecords, iTotalDisplayRecords, aaData[]}`.
- Los endpoints de guardado (`save.php`) responden `{status: "success"}` o `{status: "error", message: "..."}`.
- Las búsquedas de ciudadanos responden en formato Select2/Tom Select: `{results: [{id, text, curp?...}]}` y usan rate limiting (HTTP 429 al exceder).
- Los errores no capturados se registran en `error_logs` (visible en `public/auditoria.php`).

---

## 1. Autenticación y sesión

### POST `public/auth.php` — Iniciar sesión

| Parámetro | Tipo | Descripción |
|---|---|---|
| `correo` | string | Correo del usuario |
| `password` | string | Contraseña |

```
POST public/auth.php  (Content-Type: application/x-www-form-urlencoded)

correo=admin@drc.gob.mx&password=Admin123!
```

**Respuesta éxito:**

```json
{ "status": "success" }
```

**Respuesta error:**

```json
{ "status": "error", "message": "Credenciales inválidas" }
```

Notas: regenera el ID de sesión (`session_regenerate_id(true)`) y carga las 11 banderas de permisos en `$_SESSION`. Rechaza cuentas inactivas (`estatus = 0`).

### GET `public/logout.php` — Cerrar sesión

Redirección a `login.php` tras destruir la sesión. Sin respuesta JSON.

---

## 2. Dashboard y notificaciones

### GET `public/api/stats.php` — Estadísticas del dashboard

- Requiere sesión. Permite bypass administrativo con `?cron_token=<CRON_SECRET>` (usado por `core/CronReport.php`).

**Respuesta:**

```json
{
  "status": "success",
  "cards": {
    "tramites_hoy": 12,
    "peticiones_pendientes": 3,
    "inexistencias_pendientes": 5,
    "foraneas_validadas": 8,
    "recaudacion_total": 15200.50
  },
  "processed_by_day": { "labels": ["2026-08-10", "..."], "data": [5, 7, ...] },
  "recaudacion_proyectada": { "labels": [...], "data": [...] },
  "carga_operativa": { "labels": [...], "data": [...] }
}
```

Usa la conexión de lectura (`getReadConnection()`).

### GET `public/api/notifications.php` — Campana de notificaciones

- Requiere sesión (valida `$_SESSION['user_id']` manualmente).
- Sin parámetros. Devuelve los últimos 5 movimientos del ERP: alertas de aprobación (rol ADMIN/SUPERVISOR), estado de jobs (`completed`/`failed`) y actividad reciente de módulos.

**Respuesta:**

```json
{
  "status": "success",
  "notifications": [
    { "tipo": "job", "title": "Exportación completada", "desc": "...", "time": "hace 2 min", "icon": "fa-...", "color": "..." }
  ]
}
```

---

## 3. Tablas (DataTables server-side)

Patrón compartido por todos los módulos: `modules/<modulo>/data.php`

### Parámetros GET (los envía DataTables automáticamente)

| Parámetro | Tipo | Descripción |
|---|---|---|
| `draw` | int | Número de petición (eco) |
| `start` | int | Índice inicial |
| `length` | int | Tamaño de página |
| `search[value]` | string | Búsqueda global |
| `order[0][column]` | int | Columna de orden |
| `order[0][dir]` | string | `asc` / `desc` |

### Respuesta común

```json
{
  "draw": 1,
  "iTotalRecords": 120,
  "iTotalDisplayRecords": 34,
  "aaData": [ ["1", "JUAN PÉREZ", ...], ... ]
}
```

### `modules/ciudadanos/data.php`

- Requiere `Auth::check()` únicamente.
- Rate limit: `RateLimiter::check('ciudadanos_data', 60, 60)` → HTTP 429 al exceder 60 peticiones/min.
- Usa `getReadConnection()`.

### `modules/<nacimientos|matrimonios|divorcios|defunciones|inscripciones|reconocimientos|inexistencias|foraneas|peticiones|curp|actas_locales|reportes>/data.php`

- Requiere `Auth::check()` + `Auth::checkPermission('<bandera del módulo>')` (excepto reportes/actas_locales según su propia bandera).
- No aplican rate limit.

---

## 4. Búsqueda de ciudadanos (Select2/Tom Select)

### GET `modules/ciudadanos/search.php` — Autocompletado

| Parámetro | Tipo | Descripción |
|---|---|---|
| `q` | string | Texto de búsqueda (nombre/CURP) |
| `estado` | string (opcional) | Filtro de estado vital (`VIVO`/`FINADO`) |

- Requiere sesión.
- Rate limit: `RateLimiter::check('ciudadanos_search', 30, 60)` → HTTP 429.
- La CURP se busca cifrada con `Encryption::encrypt`.

**Respuesta:**

```json
{ "results": [ { "id": 7, "curp": "PELJ000101HDFRRN05", "text": "JUAN PÉREZ LÓPEZ" } ] }
```

---

## 5. Guardado (CRUD)

### POST `modules/<modulo>/save.php` — Alta/actualización

- Requiere sesión + permiso del módulo.
- Parámetros según formulario del módulo (ver `create.php` de cada módulo) + `csrf_token` (campo oculto de los formularios).
- Registra auditoría (`Auditoria::logAccion()`).
- `modules/peticiones/save.php` genera folio `TK-AAAA-#####` mediante `Database::generateFolio()`.

**Respuesta:**

```json
{ "status": "success" }
```

```json
{ "status": "error", "message": "La línea de pago ya existe" }
```

### POST `modules/ciudadanos/delete.php` — Baja lógica

- Requiere sesión. Valida CSRF real (`Auth::validateCSRF`).
- Parámetro: `id` (de ciudadano). Marca `estado = 0` (no elimina físicamente).

### POST `modules/peticiones/update_status.php` — Cambio de estatus de ticket

- Requiere sesión + rol ADMIN/SUPERVISOR. Valida CSRF.
- Parámetros: `id`, `estatus` ∈ {`ABIERTA`, `EN_PROGRESO`, `CERRADA`}.

### POST `public/update_usuario.php` — Administración de usuarios (ADMIN)

| Acción | Parámetros |
|---|---|
| `action=create` | `nombre`, `correo`, `password`, `rol` |
| `action=update_perms` | `id`, `rol`, `estatus`, más 11 checkboxes `permiso_*` |

Respuestas JSON `{status:success|error, message}`. Si el rol es ADMIN, fuerza todas las banderas a 1.

### POST `public/update_perfil.php` — Mi perfil

| Acción | Parámetros |
|---|---|
| `action=update_info` | `nombre`, `correo` |
| `action=change_password` | `current_password`, `new_password`, `confirm_password` |

Al cambiar contraseña regenera el ID de sesión. Respuestas JSON `{status:success|error, message}`.

---

## 6. Exportaciones (cola de jobs)

### GET `modules/<modulo>/export_excel.php` · GET `public/api/export_usuarios.php` · GET `public/api/export_auditoria.php` · GET `public/api/export_errores.php`

- Requiere sesión + rol/permiso correspondiente (exportaciones de administración: rol ADMIN).
- Parámetro opcional: `search` (filtro previo del DataTable).

**Respuesta:**

```json
{ "status": "success", "message": "La exportación está en proceso..." }
```

El archivo se genera en segundo plano: se inserta un job `pending` en la tabla `jobs` y se lanza `core/Worker.php` (vía `popen("start /B c:\xampp\php\php.exe ...")` en Windows). El `.xlsx` queda en `public/exports/` y el usuario recibe el aviso en la campana de notificaciones. Los archivos se purgan tras 48 h.

---

## 7. Administración: auditoría, errores y catálogos

### GET `public/api/auditoria_data.php` — Bitácora de auditoría (ADMIN)

- Mismo formato DataTables. Columnas: `id, fecha_hora, usuario, modulo, accion, detalles, ip_address`.
- Fuente: `auditoria_logs` LEFT JOIN `usuarios`.

### GET `public/api/errores_data.php` — Registro de errores (ADMIN)

- Mismo formato DataTables. Columnas: `id, fecha_hora, usuario, mensaje, archivo, linea, stack_trace, url` (escapadas con `htmlspecialchars`).
- Fuente: `error_logs` LEFT JOIN `usuarios`.

### GET/POST `public/api/catalogos_handler.php` — Catálogos dinámicos (ADMIN/SUPERVISOR)

| Acción | Método | Parámetros |
|---|---|---|
| `get_opciones` | GET | `catalogo` (clave del catálogo) |
| `agregar_opcion` | POST | `catalogo`, `clave`, `valor`, `orden` |
| `toggle_estado` | POST | `id`, `activo` (0/1) |

Usa `Core\Catalogo::getOpciones()/agregarOpcion()/toggleEstadoOpcion()` y registra auditoría.

**Respuesta:** `{status:success, data|message}` o `{status:error, message}`.

---

## 8. Validación pública de actas (PUVLIKA)

### GET `public/validate.php` — Verificar autenticidad de un acta

- **Público** (no requiere sesión).
- Parámetro: `token` (Base64 del código de verificación, ej. `NACIMIENTO_12`).
- Valida actas de tipo `NACIMIENTO`, `MATRIMONIO`, `DIVORCIO`, `DEFUNCION`, `RECONOCIMIENTO`.
- Respuesta: página HTML; si el token no es válido muestra "Documento Inválido".

---

## 9. HTTP Status codes usados

| Código | Uso |
|---|---|
| 200 | Éxito (JSON o HTML) |
| 302 | Redirección (login/logout) |
| 403 | Acceso denegado (sin permiso) o ruta bloqueada por `.htaccess` |
| 429 | Rate limit excedido (búsquedas de ciudadanos, `data.php` de ciudadanos) |
| 500 | Error interno (con `{status:error, message}` en JSON) |

---

## 10. Notas de seguridad conocidas

- `Auth::check()` admite bypass con `?cron_token=<CRON_SECRET>`; **rotar** `CRON_SECRET` en producción y no exponerlo.
- Varios `save.php` solo validan la *presencia* del `csrf_token`, no su validez (`Auth::validateCSRF`); considerado pendiente de endurecer.
- La ruta `c:\xampp\php\php.exe` está hardcodeada en `core/Worker.php`; parametrizar en producción (ver `docs/guia_despliegue.md`).
- Los endpoints de exportación son GET y mutan estado (insertan jobs); ideal migrarlos a POST.