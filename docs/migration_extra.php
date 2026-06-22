<?php
// docs/migration_extra.php
require_once __DIR__ . '/../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    
    // 1. Agregar columna 'estado' si no existe en la tabla ciudadanos
    $stmt = $pdo->query("SHOW COLUMNS FROM ciudadanos LIKE 'estado'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE ciudadanos ADD COLUMN estado TINYINT(1) DEFAULT 1");
        echo "Columna 'estado' agregada exitosamente a la tabla ciudadanos.\n";
    } else {
        echo "La columna 'estado' ya existe en ciudadanos.\n";
    }
    
    // 2. Crear índice para 'nombre' si no existe
    $stmtIndexNombre = $pdo->query("SHOW INDEX FROM ciudadanos WHERE Key_name = 'idx_ciudadanos_nombre'");
    if (!$stmtIndexNombre->fetch()) {
        $pdo->exec("CREATE INDEX idx_ciudadanos_nombre ON ciudadanos(nombre)");
        echo "Índice 'idx_ciudadanos_nombre' creado exitosamente.\n";
    } else {
        echo "El índice 'idx_ciudadanos_nombre' ya existe.\n";
    }
    
    // 3. Crear índice para 'curp' si no existe
    // MySQL crea automáticamente un índice por ser UNIQUE, pero garantizamos que haya un índice explícito
    $stmtIndexCurp = $pdo->query("SHOW INDEX FROM ciudadanos WHERE Column_name = 'curp'");
    if (!$stmtIndexCurp->fetch()) {
        $pdo->exec("CREATE INDEX idx_ciudadanos_curp ON ciudadanos(curp)");
        echo "Índice 'idx_ciudadanos_curp' creado exitosamente.\n";
    } else {
        echo "El índice para 'curp' ya existe (por UNIQUE o explícito).\n";
    }
    
    echo "Migración completada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error ejecutando la migración: " . $e->getMessage() . "\n";
}
