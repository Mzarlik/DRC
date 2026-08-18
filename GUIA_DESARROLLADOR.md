# Guía para Desarrolladores — ERP DRC

Cómo mantener, probar y extender el sistema respetando su arquitectura.

> Base conceptual: [`ROADMAP.md`](../ROADMAP.md) (`Blueprints` de arquitectura) y [`docs/analisis/ARQUITECTURA_ESCALABILIDAD.md`](analisis/ARQUITECTURA_ESCALABILIDAD.md).

---

## 1. Stack y organización

- **PHP 8.2+** sin framework. Clases del núcleo en namespace `Core\` (PSR-4 → `core/`).
- **Frontend:** Bootstrap 5 + FontAwesome 6 + DataTables (server-side) + SweetAlert2 + Tom Select/Select2 + Chart.js (CDN).
- **Datos:** PDO con prepared statements reales (`ATTR_EMULATE_PREPARES = false`).
- **PDF/Excel/QR:** TCPDF · PhpSpreadsheet · chillerlan/php-qrcode.

```
core/            Infraestructura (Database, Auth, Cache, Encryption, RateLimiter,
│                Auditoria, Catalogo, Utils, Worker, CronReport, Services/)
modules/         Un dir por módulo: index, create, data, save (y variantes)
public/          Docroot web: dashboard, login, auth, perfil, api/, exports/, reports/
assets/          CSS/JS globales
docs/            Documentación (este manual, API, esquema BD, planes de fase)
tests/           PHPUnit (tests/Unit) + Playwright (tests/e2e)
```

---

## 2. Comandos del entorno

```bash
composer install                    # instalar dependencias PHP
composer dump-autoload -o           # optimizar autoload tras agregar clases
vendor\bin\phpunit                  # tests unitarios (Windows)
cp .env.example .env                # configurar entorno local
php core/Worker.php                 # procesar cola de exportaciones (CLI)
php core/CronReport.php             # reporte semanal PDF (CLI)
npm install && npm run test:e2e     # tests E2E (Playwright)
```

---

## 3. Reglas de negocio obligatorias

1. **Mayúsculas:** nombres de personas, observaciones y estados → `strtoupper()` en backend; `text-transform: uppercase` en CSS.
2. **Líneas de pago / folios / CURP-numéricos:** tratar SIEMPRE como `string` (VARCHAR). Nunca `int` — evita corrupción por notación científica.
3. **Fechas:** la fecha de llegada = `fecha_tramite + DIAS_ESPERA_INEXISTENCIA` (config). Usar `Core\Utils::calcularFechaLlegada()`.
4. **Catálogo Maestro:** los trámites NO guardan nombres de personas; se vinculan por FK a `ciudadanos` mediante búsquedas AJAX.
5. **Estado vital:** registrar una defunción cambia `estado_vital → FINADO` (transaccional).
6. **Folios:** tickets y folios secuenciales usan `Database::generateFolio()` (transacción + `SELECT ... FOR UPDATE`).

---

## 4. Cómo crear un módulo nuevo (patrón estándar)

Crear la carpeta `modules/<mi_modulo>/` imitando al 100% a `modules/nacimientos`:

| Archivo | Responsabilidad |
|---|---|
| `index.php` | Vista con DataTable; guards `Auth::check()` + `Auth::checkPermission('permiso_<...>')` |
| `create.php` | Vista de alta; campo oculto `csrf_token` = `Auth::generateCSRF()` |
| `data.php` | Endpoint JSON DataTables (GET `draw/start/length/search/order` → `{draw, iTotalRecords, iTotalDisplayRecords, aaData}`) |
| `save.php` | POST; valida sesión/permiso/CSRF; sanitiza; delega a `Core\Services\`; audita; responde JSON |

Pasos en orden:

1. **Tabla SQL:** agregar el `CREATE TABLE` (o una migración en `docs/migration_*.php`), FK a `ciudadanos`/`usuarios`.
2. **Permiso:** agregar columna `permiso_<modulo>` a `usuarios` + a los 3 lugares donde se cargan las banderas (public/auth.php, public/update_usuario.php y el índice de session).
3. **Servicio:** si hay lógica de negocio compleja, crear clase en `core/Services/` (autoload automático).
4. **Código del módulo:** los 4 archivos del patrón.
5. **Menú:** registrar el enlace en el sidebar/header global (assets/js/global.js o include del layout).
6. **Exportación (opcional):** `export_excel.php` inserta job + lanza `core/Worker.php`; registrar el generador `generateXReport()` correspondiente en `Worker.php`.
7. **Seguimiento UI:** toast/changelog no son obligatorios, pero mantienen el estándar (ver `docs/versions.md`).

---

## 5. Permisos (11 banderas existentes)

`permiso_registro_nacimientos`, `permiso_registro_matrimonios`, `permiso_registro_divorcios`, `permiso_registro_defunciones`, `permiso_registro_inscripciones`, `permiso_registro_reconocimientos`, `permiso_actas_locales`, `permiso_actas_foraneas`, `permiso_constancias`, `permiso_curp`, `permiso_tickets`.

- `Auth::hasPermission($bandera)` → bool (ADMIN siempre true).
- `Auth::checkPermission($bandera)` → 403 si no; llama `check()` antes.
- Vista de administración: `public/usuarios.php` + `public/update_usuario.php` (acción `update_perms`).

---

## 6. Servicios centrales (`Core\`)

| Clase | Uso clave |
|---|---|
| `Database` | `getWriteConnection()`, `getReadConnection()` (réplica), `generateFolio()` |
| `Auth` | `check()`, `hasPermission()`, `checkPermission()`, `generateCSRF()`, `validateCSRF()` |
| `Auditoria`/`Audit` | `logAccion()` — registrar siempre toda operación de escritura |
| `Encryption` | `encrypt()/decrypt()` — CURP y datos sensibles (AES-256-CBC determinista) |
| `RateLimiter` | `check($clave, $max, $ventana_seg)` — búsquedas y data.php de ciudadanos |
| `Cache` | `get/set/delete` con fallback Redis → Memcached → archivos |
| `Catalogo` | `getOpciones()/agregarOpcion()/toggleEstadoOpcion()` — catálogos dinámicos |
| `Utils` | `calcularFechaLlegada()`, `validarLineaPago()` |
| `Worker`/`CronReport` | CLI: cola de exportaciones y reporte semanal |

---

## 7. Pruebas

- **PHPUnit (unit):** solo clases Core independientes (`tests/Unit/`): `UtilsTest`, `DatabaseTest`, `AuditoriaTest`, `QueueTest`. Ejecutar `vendor\bin\phpunit`.
- **Playwright (E2E):** `tests/e2e/login_and_register.spec.js` — flujo completo login → registro → verificación UI. Requiere el servidor corriendo y los datos de prueba, `npm run test:e2e`.
- **Regla:** todo cambio en `Core\Utils`, fechas o folios debe acompañar test unitario.

---

## 8. Frontend: convenciones

- **Tema:** variables CSS (`--primary-color` guinda/vino, `--secondary-color` dorado); soporte `.dark-mode` con script anti-FOUC en `<head>` de cada página.
- **Guardados:** preferir AJAX + toast SweetAlert2 y recarga `table.ajax.reload(null, false)`; evitar recargas de página.
- **Móvil:** acciones de fila siempre visibles (no ocultas en `:hover`); usar offcanvas para moldes largos.
- **Keyboard-first:** `Enter` avanza al siguiente campo; `Ctrl+Enter` guarda.
- **Librerías vía CDN** en `<head>`/fin de `</body>`; en redes aisladas, bajarlas a `assets/`.

---

## 9. Cifrado de CURP (leer antes de tocar `ciudadanos`)

- La CURP se guarda con `Encryption::encrypt()` (Base64 de IV determinista + ciphertext), en columna VARCHAR(255).
- La búsqueda exacta usa `WHERE curp = Encryption::encrypt($curp)`.
- `decrypt()` es retrocompatible: devuelve el valor original si no está cifrado.
- **Nunca** romper compatibilidad sin migración; documentar cambios en `docs/versions.md`.

---

## 10. Checklist de pull request

- [ ] Sin secretos en código (todo vía `.env`).
- [ ] Reglas de negocio §3 cumplidas (mayúsculas, strings numéricos, transacciones).
- [ ] Guards de sesión + permiso en cada controlador nuevo.
- [ ] CSRF en formularios (`generateCSRF`/`validateCSRF`).
- [ ] Auditoría en toda operación de escritura.
- [ ] Test unitario si afecta Core\Utils / fechas / folios.
- [ ] `composer dump-autoload` si se agregaron clases.
- [ ] Cambios de esquema: también actualizar `docs/esquema_bd.md`.