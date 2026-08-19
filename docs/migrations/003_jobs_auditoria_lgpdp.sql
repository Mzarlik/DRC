-- docs/migrations/003_jobs_auditoria_lgpdp.sql
-- Migración 003: Procesamiento Asíncrono y Auditoría Integral LGPDPPSO / INAI

-- 1. Cola de Tareas Asíncronas (Exportaciones de Reportes)
CREATE TABLE IF NOT EXISTS export_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    formato VARCHAR(20) NOT NULL DEFAULT 'XLSX',
    filtros JSON DEFAULT NULL,
    estado ENUM('PENDIENTE', 'PROCESANDO', 'COMPLETADO', 'ERROR') NOT NULL DEFAULT 'PENDIENTE',
    archivo_ruta VARCHAR(255) DEFAULT NULL,
    archivo_nombre VARCHAR(255) DEFAULT NULL,
    filas_procesadas INT DEFAULT 0,
    mensaje_error TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_estado_fecha (estado, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bitácora de Auditoría Integral (Lecturas, Escrituras y Autenticación)
CREATE TABLE IF NOT EXISTS auditoria_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    tipo_evento ENUM('ESCRITURA', 'LECTURA', 'AUTENTICACION', 'EXPORTACION') NOT NULL DEFAULT 'ESCRITURA',
    modulo VARCHAR(100) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    detalles TEXT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario_id, creado_en),
    INDEX idx_tipo_modulo (tipo_evento, modulo),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bitácora de Errores y Excepciones
CREATE TABLE IF NOT EXISTS error_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    mensaje TEXT NOT NULL,
    archivo VARCHAR(255) DEFAULT NULL,
    linea INT DEFAULT NULL,
    stack_trace LONGTEXT DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (creado_en),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
