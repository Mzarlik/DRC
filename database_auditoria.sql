CREATE TABLE IF NOT EXISTS `auditoria_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `modulo` VARCHAR(100) NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `detalles` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `fecha_hora` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `mensaje` TEXT NOT NULL,
  `archivo` VARCHAR(255) NULL,
  `linea` INT NULL,
  `stack_trace` TEXT NULL,
  `url` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NULL,
  `fecha_hora` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
