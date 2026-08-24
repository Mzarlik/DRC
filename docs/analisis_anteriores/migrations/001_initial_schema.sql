-- docs/migrations/001_initial_schema.sql
-- Migración 001: Esquema Base, Configuración, Folios y Ciudadanos con Blind Index

CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    batch INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. Tabla de Usuarios y Permisos RBAC
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN', 'COORDINADOR', 'SUPERVISOR', 'OPERADOR') NOT NULL DEFAULT 'OPERADOR',
    estatus TINYINT(1) NOT NULL DEFAULT 1,
    permiso_registro_nacimientos TINYINT(1) NOT NULL DEFAULT 0,
    permiso_registro_matrimonios TINYINT(1) NOT NULL DEFAULT 0,
    permiso_registro_divorcios TINYINT(1) NOT NULL DEFAULT 0,
    permiso_registro_defunciones TINYINT(1) NOT NULL DEFAULT 0,
    permiso_registro_inscripciones TINYINT(1) NOT NULL DEFAULT 0,
    permiso_registro_reconocimientos TINYINT(1) NOT NULL DEFAULT 0,
    permiso_actas_locales TINYINT(1) NOT NULL DEFAULT 0,
    permiso_actas_foraneas TINYINT(1) NOT NULL DEFAULT 0,
    permiso_constancias TINYINT(1) NOT NULL DEFAULT 0,
    permiso_curp TINYINT(1) NOT NULL DEFAULT 0,
    permiso_tickets TINYINT(1) NOT NULL DEFAULT 0,
    permiso_exportar TINYINT(1) NOT NULL DEFAULT 0,
    permiso_peticiones_rapidas TINYINT(1) NOT NULL DEFAULT 1,
    permiso_turnos TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador predeterminado
INSERT IGNORE INTO usuarios (id, nombre, correo, password_hash, rol, estatus, permiso_registro_nacimientos, permiso_registro_matrimonios, permiso_registro_divorcios, permiso_registro_defunciones, permiso_registro_inscripciones, permiso_registro_reconocimientos, permiso_actas_locales, permiso_actas_foraneas, permiso_constancias, permiso_curp, permiso_tickets, permiso_exportar, permiso_peticiones_rapidas, permiso_turnos) 
VALUES (1, 'ADMINISTRADOR GENERAL', 'admin@drc.gob.mx', '$2y$10$NjJAlhz.GpLzN8S9mFIwHegDQjVTUV0KNSIf/NMsce7FPgq0RWaEe', 'ADMIN', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

-- 2. Tabla de Configuración Global
CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('DIAS_ESPERA_INEXISTENCIA', '15', 'Días hábiles a sumar para la fecha de entrega de constancias'),
('NOMBRE_OFICIALIA', 'OFICIALÍA CENTRAL DEL REGISTRO CIVIL', 'Nombre institucional de la dependencia'),
('MODO_MANTENIMIENTO', '0', '1 para restringir acceso temporal');

-- 3. Tabla de Secuencia Atómica de Folios
CREATE TABLE IF NOT EXISTS folios_secuencia (
    modulo VARCHAR(50) PRIMARY KEY,
    ultimo_folio INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla Maestra de Ciudadanos con Blind Index y Soporte de Grafías Indígenas
CREATE TABLE IF NOT EXISTS ciudadanos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curp VARCHAR(255) DEFAULT NULL,               -- Retrocompatibilidad legacy
    curp_bindex VARCHAR(64) DEFAULT NULL UNIQUE,  -- Blind Index HMAC determinista para búsquedas exactas indexadas
    curp_encrypted VARCHAR(255) DEFAULT NULL,     -- Ciphertext con IV 100% aleatorio (AES-256-CBC)
    nombre VARCHAR(100) NOT NULL,                 -- Soporta: Xóchitl, Ta'an, K'an, Mää, etc.
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) DEFAULT NULL,
    fecha_nacimiento DATE NOT NULL,
    sexo ENUM('HOMBRE', 'MUJER', 'NO_BINARIO') NOT NULL,
    estado_vital ENUM('VIVO', 'FINADO') NOT NULL DEFAULT 'VIVO',
    estado TINYINT(1) NOT NULL DEFAULT 1,         -- 1=Activo, 0=Soft Delete
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre_apellidos (nombre, apellido_paterno, apellido_materno),
    INDEX idx_estado_vital (estado_vital, estado),
    INDEX idx_curp_bindex (curp_bindex)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
