<?php
// docs/migration_queue_reportes.php
require_once __DIR__ . '/../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    
    // Crear la tabla 'jobs' para las colas asíncronas
    $sql = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        payload TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        file_path VARCHAR(255) DEFAULT NULL,
        error_message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "Tabla 'jobs' creada exitosamente en la base de datos.\n";
    
    // Crear índice en 'status' y 'user_id' para optimizar lecturas
    $stmtIndex = $pdo->query("SHOW INDEX FROM jobs WHERE Key_name = 'idx_jobs_user_status'");
    if (!$stmtIndex->fetch()) {
        $pdo->exec("CREATE INDEX idx_jobs_user_status ON jobs(user_id, status)");
        echo "Índice 'idx_jobs_user_status' creado exitosamente.\n";
    }
    
    echo "Migración de colas y reportes completada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error ejecutando la migración de colas: " . $e->getMessage() . "\n";
}
