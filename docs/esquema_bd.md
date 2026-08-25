# Esquema de Base de Datos Ã¢â‚¬â€ ERP DRC

Motor: **MySQL / MariaDB** Ã‚Â· Charset: **utf8mb4** Ã‚Â· Collation: **utf8mb4_unicode_ci** Ã‚Â· Motor de tablas: **InnoDB**

Base de datos: `drc_erp`

Scripts de creaciÃƒÂ³n: `docs/database.sql` (tablas base) Ã‚Â· `database_auditoria.sql` (auditorÃƒÂ­a y errores) Ã‚Â· `docs/migration_*.php` (migraciones posteriores).

---

## 1. Diagrama de relaciones (resumen)

```
usuarios Ã¢â€â‚¬Ã¢â€â‚¬< bitacora_auditoria
usuarios Ã¢â€â‚¬Ã¢â€â‚¬< auditoria_logs
usuarios Ã¢â€â‚¬Ã¢â€â‚¬< error_logs
usuarios Ã¢â€â‚¬Ã¢â€â‚¬< peticiones (usuario_asignado)
usuarios Ã¢â€â‚¬Ã¢â€â‚¬< * (usuario_registro) en inexistencias, nacimientos, defunciones,
             foraneas, matrimonios, divorcios, reconocimientos, inscripciones, tramites_curp

ciudadanos (tabla MAESTRA)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< nacimientos   (ciudadano_id = reciÃƒÂ©n nacido; padre_id, madre_id)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< defunciones   (ciudadano_id = finado)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< foraneas      (ciudadano_id)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< peticiones    (ciudadano_id)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< matrimonios   (contrayente_1_id, contrayente_2_id)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< divorcios     (ciudadano_1_id, ciudadano_2_id)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< reconocimientos (reconocido_id, reconocedor_id)
   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬< inscripciones (ciudadano_id)
   Ã¢â€â€Ã¢â€â‚¬Ã¢â€â‚¬< tramites_curp (ciudadano_id)
```

---

## 2. Tablas

### 2.1 `usuarios` Ã¢â‚¬â€ Usuarios del sistema

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `nombre` | VARCHAR(150) NOT NULL | En MAYÃƒÅ¡SCULAS |
| `correo` | VARCHAR(150) NOT NULL UNIQUE | Inicio de sesiÃƒÂ³n |
| `password_hash` | VARCHAR(255) NOT NULL | `password_hash()` BCRYPT |
| `rol` | ENUM('ADMIN','OPERADOR','SUPERVISOR') | Default `OPERADOR` |
| `estatus` | TINYINT(1) | 1 = activo, 0 = inactivo |
| `permiso_registro_nacimientos` | TINYINT(1) | Bandera granular |
| `permiso_registro_matrimonios` | TINYINT(1) | Bandera granular |
| `permiso_registro_divorcios` | TINYINT(1) | Bandera granular |
| `permiso_registro_defunciones` | TINYINT(1) | Bandera granular |
| `permiso_registro_inscripciones` | TINYINT(1) | Bandera granular |
| `permiso_registro_reconocimientos` | TINYINT(1) | Bandera granular |
| `permiso_actas_locales` | TINYINT(1) | Bandera granular |
| `permiso_actas_foraneas` | TINYINT(1) | Bandera granular |
| `permiso_constancias` | TINYINT(1) | Bandera granular |
| `permiso_curp` | TINYINT(1) | Bandera granular |
| `permiso_tickets` | TINYINT(1) | Bandera granular |
| `creado_en` | TIMESTAMP | Default CURRENT_TIMESTAMP |
| `actualizado_en` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

Registro por defecto: `admin@drc.gob.mx` / `Admin123!` (rol ADMIN, todas las banderas en 1).

### 2.2 `configuracion` Ã¢â‚¬â€ ConfiguraciÃƒÂ³n global (clave/valor)

| Columna | Tipo | Notas |
|---|---|---|
| `clave` | VARCHAR(50) PK | Ej. `DIAS_ESPERA_INEXISTENCIA` |
| `valor` | TEXT NOT NULL | Ej. `15` |
| `descripcion` | VARCHAR(255) | |

### 2.3 `folios_secuencia` Ã¢â‚¬â€ Secuencias de folios

| Columna | Tipo | Notas |
|---|---|---|
| `modulo` | VARCHAR(50) PK | Ej. `peticiones_2026` |
| `ultimo_folio` | INT | Incrementado con `SELECT ... FOR UPDATE` en transacciÃƒÂ³n |

Generador: `Core\Database::generateFolio($modulo, $prefix, $padding)`.

### 2.4 `bitacora_auditoria` Ã¢â‚¬â€ BitÃƒÂ¡cora (primera versiÃƒÂ³n)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `usuario_id` | INT NULL | FK Ã¢â€ â€™ `usuarios.id` ON DELETE SET NULL |
| `accion` | VARCHAR(100) | INSERT / UPDATE / DELETE |
| `modulo` | VARCHAR(50) | |
| `detalles` | TEXT | |
| `ip_address` | VARCHAR(45) | IPv4/IPv6 |
| `creado_en` | TIMESTAMP | |

### 2.5 `auditoria_logs` Ã¢â‚¬â€ BitÃƒÂ¡cora estÃƒÂ¡ndar (producciÃƒÂ³n)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `usuario_id` | INT NOT NULL | FK Ã¢â€ â€™ `usuarios.id` ON DELETE CASCADE |
| `modulo` | VARCHAR(100) | |
| `accion` | VARCHAR(50) | |
| `detalles` | TEXT NULL | |
| `ip_address` | VARCHAR(45) NULL | |
| `fecha_hora` | DATETIME | Default CURRENT_TIMESTAMP |

### 2.6 `error_logs` Ã¢â‚¬â€ Errores registrados

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `usuario_id` | INT NULL | FK Ã¢â€ â€™ `usuarios.id` ON DELETE SET NULL |
| `mensaje` | TEXT NOT NULL | |
| `archivo` | VARCHAR(255) NULL | |
| `linea` | INT NULL | |
| `stack_trace` | TEXT NULL | |
| `url` | VARCHAR(255) NULL | |
| `ip_address` | VARCHAR(45) NULL | |
| `fecha_hora` | DATETIME | Default CURRENT_TIMESTAMP |

### 2.7 `jobs` Ã¢â‚¬â€ Cola de trabajos (exportaciones)

Tabla de la cola de exportaciones procesada por `core/Worker.php` (script CLI que procesa hasta 5 jobs `pending` por ejecuciÃƒÂ³n). Campos tÃƒÂ­picos: `id`, `type` (ej. `export_nacimientos`, `export_usuarios`, `export_auditoria`, `export_errores`, `export_ciudadanos`, `export_defunciones`, `export_reportes`, ...), `params` (JSON), `status` (`pending Ã¢â€ â€™ processing Ã¢â€ â€™ completed | failed`), `created_at`, `processed_at`. Los archivos `.xlsx` generados se escriben en `public/exports/` y se purgan despuÃƒÂ©s de 48 h.

---

## 3. MÃƒÂ³dulos de negocio

### 3.1 `inexistencias` Ã¢â‚¬â€ Constancias de inexistencia

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `tipo_constancia` | ENUM('INEXISTENCIA_NACIMIENTO','INEXISTENCIA_MATRIMONIO','INEXISTENCIA_DESCENDENCIA','NO_DEUDOR') | Default primera |
| `linea_pago` | VARCHAR(25) NOT NULL UNIQUE | **String** (17-25 alfanumÃƒÂ©rico, nunca entero) |
| `fecha_tramite` | DATE NOT NULL | |
| `fecha_llegada` | DATE NOT NULL | `fecha_tramite` + N dÃƒÂ­as (config `DIAS_ESPERA_INEXISTENCIA`) |
| `nombre_completo` | VARCHAR(250) NOT NULL | MAYÃƒÅ¡SCULAS |
| `estatus` | ENUM('PENDIENTE','FINALIZADO','CANCELADO') | |
| `observaciones` | TEXT NULL | MAYÃƒÅ¡SCULAS |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios.id` ON DELETE SET NULL |
| `creado_en` / `actualizado_en` | TIMESTAMP | |

### 3.2 `ciudadanos` Ã¢â‚¬â€ CatÃƒÂ¡logo MAESTRO de identidades

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `curp` | VARCHAR(18) UNIQUE NULL | **Cifrada AES-256-CBC determinista** (almacenada en Base64, VARCHAR(255) en migraciÃƒÂ³n) |
| `nombre` | VARCHAR(100) NOT NULL | MAYÃƒÅ¡SCULAS |
| `apellido_paterno` | VARCHAR(100) NOT NULL | MAYÃƒÅ¡SCULAS |
| `apellido_materno` | VARCHAR(100) NULL | MAYÃƒÅ¡SCULAS |
| `sexo` | ENUM('M','F','X') NOT NULL | |
| `fecha_nacimiento` | DATE NOT NULL | |
| `estado_vital` | ENUM('VIVO','FINADO') | Default `VIVO`; pasa a `FINADO` al registrar defunciÃƒÂ³n |
| `estado` | TINYINT(1) | Soft delete (0 = baja lÃƒÂ³gica) |
| `creado_en` / `actualizado_en` | TIMESTAMP | |

### 3.3 `nacimientos`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `numero_acta` | VARCHAR(25) NOT NULL UNIQUE | |
| `ciudadano_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE (reciÃƒÂ©n nacido) |
| `padre_id` | INT NULL | FK Ã¢â€ â€™ `ciudadanos` SET NULL |
| `madre_id` | INT NULL | FK Ã¢â€ â€™ `ciudadanos` SET NULL |
| `lugar_nacimiento` | VARCHAR(250) NOT NULL | |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `estatus` | ENUM('REGISTRADO','CANCELADO') | Default `REGISTRADO`; migraciÃƒÂ³n migration_estatus_actas.php |
| `creado_en` | TIMESTAMP | |

### 3.4 `defunciones`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `numero_acta` | VARCHAR(25) NOT NULL UNIQUE | |
| `ciudadano_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE (finado) |
| `fecha_defuncion` | DATE NOT NULL | |
| `causa_muerte` | VARCHAR(250) NOT NULL | |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `estatus` | ENUM('REGISTRADO','CANCELADO') | Default `REGISTRADO`; migraciÃƒÂ³n migration_estatus_actas.php |

Regla de negocio: al crear una defunciÃƒÂ³n, `estado_vital` del ciudadano cambia a `FINADO` (transaccional).

### 3.5 `foraneas` Ã¢â‚¬â€ Actas de otros estados

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `estado_origen` | VARCHAR(100) NOT NULL | |
| `numero_acta` | VARCHAR(25) NOT NULL | |
| `tipo_acta` | ENUM('NACIMIENTO','DEFUNCION','MATRIMONIO','DIVORCIO','RECONOCIMIENTO','OTRO') | |
| `ciudadano_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `fecha_recepcion` | DATE NOT NULL | |
| `estatus` | ENUM('PENDIENTE','VALIDADA','RECHAZADA') | |
| `observaciones` | TEXT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
### 3.6 `peticiones` Ã¢â‚¬â€ Tickets / Mesa de ayuda

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `folio` | VARCHAR(20) NOT NULL UNIQUE | Generado `TK-AAAA-#####` |
| `ciudadano_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `tipo_peticion` | ENUM('CORRECCION_ACTA','DIGITALIZACION','ACLARACION','OTRO') | |
| `descripcion` | TEXT NOT NULL | |
| `estatus` | ENUM('ABIERTA','EN_PROGRESO','CERRADA') | |
| `usuario_asignado` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `fecha_creacion` | TIMESTAMP | |
| `fecha_cierre` | TIMESTAMP NULL | |

### 3.7 `matrimonios`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `numero_acta` | VARCHAR(25) NOT NULL UNIQUE | |
| `contrayente_1_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `contrayente_2_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `regimen_patrimonial` | VARCHAR(100) NOT NULL | |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `estatus` | ENUM('REGISTRADO','CANCELADO') | Default `REGISTRADO`; migraciÃƒÂ³n migration_estatus_actas.php |

### 3.8 `divorcios`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `numero_acta` | VARCHAR(25) NOT NULL UNIQUE | |
| `ciudadano_1_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `ciudadano_2_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `tipo_divorcio` | ENUM('JUDICIAL','ADMINISTRATIVO') | |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `estatus` | ENUM('REGISTRADO','CANCELADO') | Default `REGISTRADO`; migraciÃƒÂ³n migration_estatus_actas.php |

### 3.9 `reconocimientos`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `numero_acta` | VARCHAR(25) NOT NULL UNIQUE | |
| `reconocido_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `reconocedor_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `estatus` | ENUM('REGISTRADO','CANCELADO') | Default `REGISTRADO`; migraciÃƒÂ³n migration_estatus_actas.php |

### 3.10 `inscripciones`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `numero_acta` | VARCHAR(25) NOT NULL UNIQUE | |
| `ciudadano_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `pais_origen` | VARCHAR(100) NOT NULL | |
| `documento_extranjero` | TEXT NOT NULL | Datos de apostilla/registro |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
| `estatus` | ENUM('REGISTRADO','CANCELADO') | Default `REGISTRADO`; migraciÃƒÂ³n migration_estatus_actas.php |

### 3.11 `tramites_curp`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT PK AI | |
| `ciudadano_id` | INT NOT NULL | FK Ã¢â€ â€™ `ciudadanos` CASCADE |
| `tipo_solicitud` | ENUM('ALTA','BAJA','CORRECCION') | |
| `estatus` | ENUM('PROCESADO','PENDIENTE','RECHAZADO') | |
| `fecha_registro` | DATE NOT NULL | |
| `usuario_registro` | INT NULL | FK Ã¢â€ â€™ `usuarios` SET NULL |
---

## 4. Migraciones posteriores (`docs/migration_*.php`)

| Script | Cambio |
|---|---|
| `migration_encrypt.php` | AmpliaciÃƒÂ³n de `ciudadanos.curp` a VARCHAR(255) e instalaciÃƒÂ³n de cifrado determinista AES-256-CBC |
| `migration_extra.php` | Tablas complementarias de auditorÃƒÂ­a/errores y otras extensiones |
| `migration_queue_reportes.php` | Tabla `jobs` (cola de exportaciones) e integraciÃƒÂ³n con `core/Worker.php` |

Ejecutar cada migraciÃƒÂ³n **una sola vez** con `php docs/migration_*.php` (validan si ya fueron aplicadas).

---

## 5. ÃƒÂndices recomendados

Ya instalados (Fase 10) en columnas de alta demanda:

- `ciudadanos.curp` (UNIQUE)
- `ciudadanos.nombre`, `apellido_paterno`, `apellido_materno` (ÃƒÂ­ndices de bÃƒÂºsqueda)

Para rÃƒÂ©plicas de lectura (Fase 11): los listados y reportes deben consultar vÃƒÂ­a `getReadConnection()`.