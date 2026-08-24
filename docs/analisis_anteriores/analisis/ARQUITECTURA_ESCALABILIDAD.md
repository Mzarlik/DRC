# Análisis del Proyecto — Arquitectura y Escalabilidad

**Proyecto:** ERP DRC (Dirección de Registro Civil)
**Fecha de revisión:** 2026-08-17
**Estado:** Pre-deploy

---

## 1. Veredicto general

**ESCALABILIDAD: ACEPTABLE** para el contexto (oficina municipal de registro civil).
El proyecto está bien diseñado para su escala actual y tiene decisiones correctas de
arquitectura (procesamiento server-side, cola de trabajos, réplica de lectura).
Su límite principal es ser monolítico PHP y no escalar horizontalmente sin cambios.

---

## 2. Fortalezas de escalabilidad

| Decisión | Dónde | Beneficio |
|---|---|---|
| DataTables server-side | Todos los `data.php` de módulos | Pagina y filtra en BD; no carga miles de filas al navegador |
| Cola de trabajos (`jobs` + worker CLI) | `core/Worker.php` | Exportes Excel pesados no bloquean la petición web |
| Réplica de lectura opcional | `core/Database.php:76-100` | Separa lecturas de escrituras; fallback seguro a master |
| Singleton PDO | `core/Database.php` | Una conexión por request; ahorra conexiones |
| Caché multi-motor | `core/Cache.php` | Redis → Memcached → archivos, con fallback automático |
| Folios atómicos | `core/Database.php:152-185` | `SELECT ... FOR UPDATE` elimina condiciones de carrera entre usuarios concurrentes |
| ORM-free + prepared statements | Todo el código | Consultas eficientes sin capas pesadas |
| Soft-delete de ciudadanos | `modules/ciudadanos/delete.php` | Preserva integridad referencial |

## 3. Limitaciones para escalar

1. **Vertical, no horizontal**:
   - La caché de archivos en `cache/` no sirve con 2+ servidores (solo serve Redis/Memcached).
   - La sesión PHP por defecto (archivos) tampoco es compartida entre servidores.
   - **Impacto**: bajo para un solo servidor; relevante si se duplica el load.

2. **Crecimiento de tablas sin índices**:
   - El esquema base (`docs/database.sql`) solo define UNIQUE y FKs; faltan índices
     secundarios para búsquedas por `nombre`/`curp` (FKs se indexan solas en InnoDB).
   - Mitigado parcialmente por `docs/migration_extra.php` (índices sobre
     `ciudadanos.nombre`, `ciudadanos.curp`, `jobs.user_id`), pero no en las demás
     tablas de actas (búsquedas por `numero_acta` usan UNIQUE — bien).

3. **Búsquedas con `LIKE '%...%'`**:
   - El server-side de DataTables usa búsqueda substring; con cientos de miles de
     actas degradará. Opciones: índices FULLTEXT o búsqueda por prefijo.

4. **CRC CURP encriptada** (`Encryption.php`):
   - Solución determinista correcta para búsquedas exactas, pero impide índices
     parciales y búsquedas difusas sobre CURP.

5. **Worker dependiente de cron**:
   - Requiere un cron programado (no hay proceso demonio); si falla el cron,
     los exportes quedan `pending` indefinidamente.

6. **Exportes síncronos vía `popen`**:
   - `popen("start /B ... php.exe")` desde peticiones web (`Worker.php:29` y 11
     exportadores) lanza un proceso PHP por exporte — costoso y no portátil
     (hardcodeado a `c:\xampp\php\php.exe`).

7. **Auditoría en BD sin purga**:
   - `auditoria_logs`/`error_logs` crecen sin rotación; planificar purga por
     antigüedad (ej. retener 12-24 meses).

---

## 4. Proyección de crecimiento

| Escenario | Comportamiento esperado |
|---|---|
| 10-50 usuarios concurrentes | Sólido sin cambios (paginación server-side, cola de jobs) |
| >100.000 actas por tabla | Añadir índices secundarios y/o FULLTEXT; considerar particionado por año |
| 2+ servidores web | Requiere Redis compartido + sesiones en Redis/BD |
| Exportes frecuentes | Subir frecuencia del worker cron; considerar cola dedicada |

## 5. Recomendaciones

1. **Producción**: usar siempre Redis si hay >30 usuarios concurrentes.
2. **Mantener cron del worker**: `* * * * * php core/Worker.php` (o cada 2 min).
3. **Agregar índices** a consultas frecuentes detectadas con `EXPLAIN` cuando la BD crezca.
4. **Parametrizar** la ruta de `php.exe` del worker según el servidor de producción.
5. **Los exportes** deben escribirse fuera del docroot (ver análisis de seguridad).