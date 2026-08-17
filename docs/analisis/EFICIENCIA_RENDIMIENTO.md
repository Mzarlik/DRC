# Análisis del Proyecto — Eficiencia y Rendimiento

**Proyecto:** ERP DRC (Dirección de Registro Civil)
**Fecha de revisión:** 2026-08-17
**Estado:** Pre-deploy

---

## 1. Veredicto general

**EFICIENCIA: BUENA, con huecos de optimización puntuales.**
El código evita los antipatrones graves (consulta por fila en bucles con N+1
generalizado, carga masiva al navegador) y las consultas críticas son correctas.
Los puntos débiles son índices, algunas respuestas con datos crudos y costos
de I/O repetidos.

---

## 2. Puntos fuertes

| Práctica | Dónde | Impacto |
|---|---|---|
| PDO `ERRMODE_EXCEPTION` + `EMULATE_PREPARES=false` | `core/Database.php:57-62` | Consultas reales en servidor, sin reescritura emulada |
| Singleton de conexión | `core/Database.php:12-14` | 1 conexión por request |
| Server-side processing | Todos los `data.php` | EVITA el N+1 del lado cliente; solo envía la página de datos |
| Parámetros `(int)` para límite/offset | `modules/reportes/data.php:182` | Neutraliza inyección y malformación |
| Fechas generadas por `date()` en SQL | `public/api/stats.php` | Costo de legibilidad, no de seguridad |
| Caché de catálogos | `core/Catalogo.php` + `Cache.php` | Catálogos nacionales (estados, municipios) no se recalculan |
| Encriptación determinista de CURP | `core/Encryption.php` | Permite `WHERE curp = ?` exacto sin descifrar en servidor |
| Respaldo de API con caché | `Cache::get/set` en catálogos | Menor carga de BD |

## 3. Puntos débiles

1. **Índices secundarios insuficientes** (`docs/database.sql`):
   - Solo UNIQUE + FKs. Faltan índices para los `WHERE` del server-side
     (filtros por `estatus`, fechas, usuario).
   - `docs/migration_extra.php` añade 3 índices (ciudadanos.nombre, ciudadanos.curp,
     jobs.user_id/status) — deben aplicarse y extenderse al resto de tablas.

2. **Búsqueda `LIKE '%...%'` de DataTables**:
   - GlobalSearch hace `LIKE` con comodines al inicio → full scan por tabla.
   - Con decenas de miles de filas, la búsqueda será lenta; opción: FULLTEXT o
     limitar búsqueda a columnas indexadas.

3. **Lectura del `.env` repetida por request**:
   - `loadEnv()` corre en `Database`, `Auth` y `Encryption`; `parse_ini_file` +
   `hash('sha256')` en cada request de cada archivo que incluye esos módulos.
   - Impacto bajo (I/O local), pero mejorable con `static::$env` cacheado.

4. **Excepción → `$e->getMessage()` en respuestas JSON**:
   - Varios endpoints devuelven el mensaje crudo de PDO al cliente
     (ej. `stats.php:140`, `notifications.php:207`, `get_details.php:93`,
     `reportes/data.php:201`, exportadores). Además de fuga de información,
     serializa el error a cada request.

5. **Exportes pesados síncronos** (`popen` con `start /B`):
   - Generan trabajo de CPU/RAM por request web sin espera de cola.
   - El worker CLI es la vía correcta; los exportadores directos deben eliminarse o
     delegarse a la cola.

6. **Formato de fechas en cada DataTables**:
   - Transformación de fechas por fila en PHP (repetida en cada módulo);
     costo trivial, pero duplicación de lógica (mantenimiento).

7. **`unserialize` de caché de archivos** (`Cache.php:76,89`):
   - Correcto para datos propios, pero la carpeta `cache/` con `0777` es auditable;
     si se logra escribir ahí, objeto inyectado (ver informe de seguridad).

---

## 4. Recomendaciones de rendimiento

1. **Aplicar migraciones de índices** en el servidor de producción antes del deploy.
2. **Medir con `EXPLAIN`** las consultas del server-side al crecer la BD; añadir
   índices compuestos (ej. `(estatus, fecha)`, `(usuario_registro, fecha)`).
3. **Cachear `.env`** en una propiedad estática (una sola lectura por proceso).
4. **Estandarizar** el error JSON: `['status' => 'error', 'message' => 'Error interno']`
   y registrar la excepción en `error_logs` (que ya existe vía `Auditoria`).
5. **Mover todos los exportes a la cola** `jobs` + worker CLI.
6. **OPcache activado** en producción (`opcache.enable=1`, `validate_timestamps=0` en
   producción estable) — no verificado aún en el entorno XAMPP.