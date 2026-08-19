-- docs/migrations/002_actos_registrales.sql
-- Migración 002: Tablas de Actos Registrales y Trámites de Ventanilla

-- 1. Nacimientos
CREATE TABLE IF NOT EXISTS nacimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL,
    padre_id INT DEFAULT NULL,
    madre_id INT DEFAULT NULL,
    fecha_nacimiento DATE NOT NULL,
    fecha_registro DATE NOT NULL,
    lugar_nacimiento VARCHAR(255) NOT NULL,
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (padre_id) REFERENCES ciudadanos(id) ON DELETE SET NULL,
    FOREIGN KEY (madre_id) REFERENCES ciudadanos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Matrimonios
CREATE TABLE IF NOT EXISTS matrimonios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL UNIQUE,
    contrayente_1_id INT NOT NULL,
    contrayente_2_id INT NOT NULL,
    fecha_registro DATE NOT NULL,
    regimen_patrimonial ENUM('SOCIEDAD_CONYUGAL', 'SEPARACION_BIENES', 'MIXTO') NOT NULL DEFAULT 'SOCIEDAD_CONYUGAL',
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrayente_1_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (contrayente_2_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Divorcios
CREATE TABLE IF NOT EXISTS divorcios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL UNIQUE,
    ciudadano_1_id INT NOT NULL,
    ciudadano_2_id INT NOT NULL,
    fecha_registro DATE NOT NULL,
    tipo_divorcio ENUM('ADMINISTRATIVO', 'JUDICIAL', 'INCAUSADO') NOT NULL DEFAULT 'ADMINISTRATIVO',
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_1_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (ciudadano_2_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Defunciones
CREATE TABLE IF NOT EXISTS defunciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL,
    fecha_defuncion DATE NOT NULL,
    fecha_registro DATE NOT NULL,
    lugar_defuncion VARCHAR(255) NOT NULL,
    causa_defuncion VARCHAR(255) NOT NULL,
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Inscripciones
CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL,
    tipo_acto VARCHAR(50) NOT NULL,
    lugar_origen VARCHAR(255) NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Reconocimientos
CREATE TABLE IF NOT EXISTS reconocimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(50) NOT NULL UNIQUE,
    reconocido_id INT NOT NULL,
    reconocedor_id INT NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reconocido_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (reconocedor_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Inexistencias (Constancias)
CREATE TABLE IF NOT EXISTS inexistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    nombre_solicitante VARCHAR(200) NOT NULL,
    tipo_acto ENUM('NACIMIENTO', 'MATRIMONIO', 'DEFUNCION') NOT NULL,
    fecha_solicitud DATE NOT NULL,
    fecha_llegada DATE NOT NULL,
    linea_pago VARCHAR(50) NOT NULL,
    estatus ENUM('PENDIENTE', 'BUSQUEDA', 'EXPEDIDA', 'ENTREGADA') NOT NULL DEFAULT 'PENDIENTE',
    usuario_registro INT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_estatus_fecha (estatus, fecha_llegada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Actas Foráneas
CREATE TABLE IF NOT EXISTS actas_foraneas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL,
    estado_origen VARCHAR(100) NOT NULL,
    municipio_origen VARCHAR(150) NOT NULL,
    tipo_acto VARCHAR(50) NOT NULL,
    fecha_solicitud DATE NOT NULL,
    estatus ENUM('SOLICITADA', 'RECIBIDA', 'ENTREGADA', 'RECHAZADA') NOT NULL DEFAULT 'SOLICITADA',
    usuario_registro INT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id),
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Peticiones de Ventanilla y Tickets
CREATE TABLE IF NOT EXISTS peticiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    ciudadano_id INT DEFAULT NULL,
    tipo_tramite VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    estatus ENUM('PENDIENTE', 'EN_PROCESO', 'ATENDIDA', 'CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    usuario_registro INT DEFAULT NULL,
    usuario_asignado INT DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at DATETIME DEFAULT NULL,
    deleted_by INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_asignado) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_estatus_tramite (estatus, tipo_tramite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Turnos de Atención
CREATE TABLE IF NOT EXISTS turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_turno VARCHAR(20) NOT NULL,
    modulo_atencion VARCHAR(50) DEFAULT 'VENTANILLA 1',
    estatus ENUM('ESPERA', 'LLAMANDO', 'ATENDIENDO', 'FINALIZADO', 'CANCELADO') NOT NULL DEFAULT 'ESPERA',
    prioridad ENUM('NORMAL', 'PREFERENCIAL') NOT NULL DEFAULT 'NORMAL',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estatus_turno (estatus, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
