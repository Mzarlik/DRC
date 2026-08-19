# Plan de Implementación — Fase 3: Base de Datos, Migraciones Versionadas, Blind Index, Prevención de Deadlocks y Auditoría LGPDPPSO

Este plan detalla los pasos técnicos y operativos para ejecutar la **Fase 3** del despliegue del ERP DRC, estableciendo un motor de migraciones versionadas (`core/Migrate.php`), blindando la privacidad de la CURP mediante **Blind Index**, implementando **resiliencia contra Deadlocks** y asegurando la **auditoría de lecturas (LGPDPPSO / INAI)**.

---

## User Review Required

> [!IMPORTANT]
> **Motor de Migraciones Versionadas (`core/Migrate.php`):**
> Se creará el orquestador formal de migraciones CLI con tabla de control `schema_migrations`.
> Los archivos DDL se organizarán secuencialmente en `docs/migrations/*.sql` (`001_initial_schema.sql`, `002_actos_registrales.sql`, `003_jobs_auditoria_lgpdp.sql`).

> [!NOTE]
> **Blind Index en Búsquedas de Ciudadanos:**
> Se actualizarán `modules/ciudadanos/save.php` y `modules/ciudadanos/search.php` para guardar `curp_encrypted` (con IV aleatorio) y `curp_bindex` (hash HMAC determinista), permitiendo búsquedas indexadas ultra rápidas sin exponer el dato a análisis de inferencia.

---

## Proposed Changes

### 1. Motor de Migraciones y DDL Versionado

#### [NEW] [core/Migrate.php](file:///C:/xampp/htdocs/DRC/core/Migrate.php)
- Ejecutor CLI con comandos `up`, `status` y `rollback`.
- Creación automática de la tabla `schema_migrations` y ejecución transaccional de scripts SQL.

#### [NEW] [docs/migrations/001_initial_schema.sql](file:///C:/xampp/htdocs/DRC/docs/migrations/001_initial_schema.sql)
- Esquema de `usuarios`, `configuracion`, `folios_secuencia` y `ciudadanos` (con soporte UTF-8 para grafías indígenas y columnas `curp_bindex` / `curp_encrypted`).

#### [NEW] [docs/migrations/002_actos_registrales.sql](file:///C:/xampp/htdocs/DRC/docs/migrations/002_actos_registrales.sql)
- Tablas de actos de estado civil: `nacimientos`, `matrimonios`, `divorcios`, `defunciones`, `inscripciones`, `reconocimientos`, `inexistencias`, `foraneas`, `tickets`, `peticiones` con llaves foráneas e índices.

#### [NEW] [docs/migrations/003_jobs_auditoria_lgpdp.sql](file:///C:/xampp/htdocs/DRC/docs/migrations/003_jobs_auditoria_lgpdp.sql)
- Tablas de infraestructura: `export_jobs`, `auditoria_logs` (con columna `tipo_evento ENUM('ESCRITURA','LECTURA','AUTENTICACION','EXPORTACION')`) y `error_logs`.

---

### 2. Prevención de Deadlocks y Reintentos Automáticos

#### [MODIFY] [core/Database.php](file:///C:/xampp/htdocs/DRC/core/Database.php)
- Implementar `Database::transactionWithRetry(callable $callback, int $maxRetries = 3)` con backoff exponencial y jitter ante errores MySQL 1213 / SQLSTATE 40001.

---

### 3. Auditoría de Lecturas (Cumplimiento LGPDPPSO / INAI)

#### [MODIFY] [core/Auditoria.php](file:///C:/xampp/htdocs/DRC/core/Auditoria.php)
- Añadir método `logLectura(string $modulo, string $accion, string $detalles)` para registrar consultas de expedientes biográficos y búsquedas masivas.
- Actualizar `logAccion` para poblar la columna `tipo_evento = 'ESCRITURA'`.

---

### 4. Blind Index en Módulo de Ciudadanos

#### [MODIFY] [modules/ciudadanos/save.php](file:///C:/xampp/htdocs/DRC/modules/ciudadanos/save.php)
- Almacenar simultáneamente `curp_bindex` (HMAC para índices) y `curp_encrypted` (AES-256).

#### [MODIFY] [modules/ciudadanos/search.php](file:///C:/xampp/htdocs/DRC/modules/ciudadanos/search.php)
- Consultar por `curp_bindex = :bindex` cuando se busca una CURP exacta.
- Registrar evento de lectura en bitácora mediante `Auditoria::logLectura()`.

---

### 5. Pruebas Unitarias de Verificación

#### [NEW] [tests/Unit/DeadlockRetryTest.php](file:///C:/xampp/htdocs/DRC/tests/Unit/DeadlockRetryTest.php)
- Prueba unitaria que simula excepciones de Deadlock y valida la recuperación automática.

#### [MODIFY] [tests/Unit/EncryptionTest.php](file:///C:/xampp/htdocs/DRC/tests/Unit/EncryptionTest.php)
- Validar consistencia de `getBlindIndex()` y compatibilidad de cifrado.

---

## Verification Plan

### Automated Tests
- Ejecutar suite de pruebas unitarias:
  ```bash
  C:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/EncryptionTest.php tests/Unit/DeadlockRetryTest.php tests/Unit/UtilsTest.php
  ```
- Probar comando de estado de migraciones:
  ```bash
  C:\xampp\php\php.exe core/Migrate.php status
  ```

### Manual Verification
1. Probar la búsqueda de ciudadanos por CURP en `modules/ciudadanos/search.php` y corroborar que se registre en `auditoria_logs` con `tipo_evento = 'LECTURA'`.
2. Validar que un Deadlock simulado se reintente sin interrumpir al usuario.
