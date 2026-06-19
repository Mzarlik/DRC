-- Script SQL Inicial para el ERP de la Dirección de Registro Civil
-- Motor recomendado: MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS drc_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE drc_erp;

-- --------------------------------------------------------
-- 1. Tabla de Usuarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN', 'OPERADOR', 'SUPERVISOR') DEFAULT 'OPERADOR',
    estatus TINYINT(1) DEFAULT 1,
    permiso_registro_nacimientos TINYINT(1) DEFAULT 1,
    permiso_registro_matrimonios TINYINT(1) DEFAULT 1,
    permiso_registro_divorcios TINYINT(1) DEFAULT 1,
    permiso_registro_defunciones TINYINT(1) DEFAULT 1,
    permiso_registro_inscripciones TINYINT(1) DEFAULT 1,
    permiso_registro_reconocimientos TINYINT(1) DEFAULT 1,
    permiso_actas_locales TINYINT(1) DEFAULT 1,
    permiso_actas_foraneas TINYINT(1) DEFAULT 1,
    permiso_constancias TINYINT(1) DEFAULT 1,
    permiso_curp TINYINT(1) DEFAULT 1,
    permiso_tickets TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar administrador por defecto (admin@drc.gob.mx / Admin123!)
INSERT IGNORE INTO usuarios (nombre, correo, password_hash, rol) VALUES 
('Administrador', 'admin@drc.gob.mx', '$2y$10$NjJAlhz.GpLzN8S9mFIwHegDQjVTUV0KNSIf/NMsce7FPgq0RWaEe', 'ADMIN');

-- --------------------------------------------------------
-- 2. Tabla de Configuración Global
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    descripcion VARCHAR(255)
);

-- Insertar configuración por defecto para días hábiles de la constancia
INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES ('DIAS_ESPERA_INEXISTENCIA', '15', 'Días a sumar para la fecha de llegada del trámite de inexistencia');

-- --------------------------------------------------------
-- 3. Tabla de Logs (Auditoría)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    detalles TEXT,
    ip_address VARCHAR(45),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 4. Tabla del Módulo: Inexistencias (Fase 1)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS inexistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_constancia ENUM('INEXISTENCIA_NACIMIENTO', 'INEXISTENCIA_MATRIMONIO', 'INEXISTENCIA_DESCENDENCIA', 'NO_DEUDOR') DEFAULT 'INEXISTENCIA_NACIMIENTO',
    linea_pago VARCHAR(25) NOT NULL UNIQUE, -- Tratado estrictamente como String
    fecha_tramite DATE NOT NULL,
    fecha_llegada DATE NOT NULL,
    nombre_completo VARCHAR(250) NOT NULL, -- Siempre en MAYÚSCULAS
    estatus ENUM('PENDIENTE', 'FINALIZADO', 'CANCELADO') DEFAULT 'PENDIENTE',
    observaciones TEXT, -- Siempre en MAYÚSCULAS
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 5. Tabla Maestra: Ciudadanos (Fase 2)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ciudadanos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curp VARCHAR(18) UNIQUE, -- Puede ser nulo en caso de no tener, pero si se ingresa debe ser único
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100),
    sexo ENUM('M', 'F', 'X') NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    estado_vital ENUM('VIVO', 'FINADO') DEFAULT 'VIVO',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 6. Tabla del Módulo: Nacimientos (Fase 2)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS nacimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(25) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL, -- El recién nacido
    padre_id INT,
    madre_id INT,
    lugar_nacimiento VARCHAR(250) NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (padre_id) REFERENCES ciudadanos(id) ON DELETE SET NULL,
    FOREIGN KEY (madre_id) REFERENCES ciudadanos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 7. Tabla del Módulo: Defunciones (Fase 2)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS defunciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(25) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL, -- El finado
    fecha_defuncion DATE NOT NULL,
    causa_muerte VARCHAR(250) NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 8. Tabla del Módulo: Foráneas (Fase 3)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS foraneas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estado_origen VARCHAR(100) NOT NULL,
    numero_acta VARCHAR(25) NOT NULL,
    tipo_acta ENUM('NACIMIENTO', 'DEFUNCION', 'MATRIMONIO', 'DIVORCIO', 'RECONOCIMIENTO', 'OTRO') NOT NULL,
    ciudadano_id INT NOT NULL,
    fecha_recepcion DATE NOT NULL,
    estatus ENUM('PENDIENTE', 'VALIDADA', 'RECHAZADA') DEFAULT 'PENDIENTE',
    observaciones TEXT,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 9. Tabla del Módulo: Peticiones / Tickets (Fase 3)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS peticiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL,
    tipo_peticion ENUM('CORRECCION_ACTA', 'DIGITALIZACION', 'ACLARACION', 'OTRO') NOT NULL,
    descripcion TEXT NOT NULL,
    estatus ENUM('ABIERTA', 'EN_PROGRESO', 'CERRADA') DEFAULT 'ABIERTA',
    usuario_asignado INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_asignado) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 10. Tablas para Expansión de Módulos (Fase 5)
-- --------------------------------------------------------

-- Tabla de Matrimonios
CREATE TABLE IF NOT EXISTS matrimonios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(25) NOT NULL UNIQUE,
    contrayente_1_id INT NOT NULL,
    contrayente_2_id INT NOT NULL,
    regimen_patrimonial VARCHAR(100) NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrayente_1_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (contrayente_2_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de Divorcios
CREATE TABLE IF NOT EXISTS divorcios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(25) NOT NULL UNIQUE,
    ciudadano_1_id INT NOT NULL,
    ciudadano_2_id INT NOT NULL,
    tipo_divorcio ENUM('JUDICIAL', 'ADMINISTRATIVO') NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_1_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (ciudadano_2_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de Reconocimientos
CREATE TABLE IF NOT EXISTS reconocimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(25) NOT NULL UNIQUE,
    reconocido_id INT NOT NULL,
    reconocedor_id INT NOT NULL,
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reconocido_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (reconocedor_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de Inscripciones
CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_acta VARCHAR(25) NOT NULL UNIQUE,
    ciudadano_id INT NOT NULL,
    pais_origen VARCHAR(100) NOT NULL,
    documento_extranjero TEXT NOT NULL, -- Datos de apostilla/registro
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de Trámites CURP
CREATE TABLE IF NOT EXISTS tramites_curp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ciudadano_id INT NOT NULL,
    tipo_solicitud ENUM('ALTA', 'BAJA', 'CORRECCION') NOT NULL,
    estatus ENUM('PROCESADO', 'PENDIENTE', 'RECHAZADO') DEFAULT 'PENDIENTE',
    fecha_registro DATE NOT NULL,
    usuario_registro INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
);
