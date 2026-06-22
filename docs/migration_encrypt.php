<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';

use Core\Database;
use Core\Encryption;

try {
    $pdo = Database::getWriteConnection();
    
    // 1. Ampliar el tamaño de la columna 'curp' en 'ciudadanos' para almacenar el base64 cifrado
    echo "Modificando la columna 'curp' a VARCHAR(255) en la tabla 'ciudadanos'...\n";
    $pdo->exec("ALTER TABLE ciudadanos MODIFY COLUMN curp VARCHAR(255) NULL UNIQUE");
    echo "Columna 'curp' modificada con éxito.\n";
    
    // 2. Seleccionar y encriptar los registros existentes
    echo "Buscando CURPs en texto plano para encriptar...\n";
    $stmt = $pdo->query("SELECT id, curp FROM ciudadanos WHERE curp IS NOT NULL AND CHAR_LENGTH(curp) <= 18");
    $ciudadanos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $pdo->beginTransaction();
    
    $stmtUpdate = $pdo->prepare("UPDATE ciudadanos SET curp = :curp WHERE id = :id");
    
    foreach ($ciudadanos as $c) {
        $plainCurp = $c['curp'];
        // Validar si realmente es texto plano de longitud <= 18
        if (strlen($plainCurp) > 18) {
            continue;
        }
        
        $encrypted = Encryption::encrypt($plainCurp);
        $stmtUpdate->execute([
            ':curp' => $encrypted,
            ':id' => $c['id']
        ]);
        $updated++;
    }
    
    $pdo->commit();
    echo "Migración completada. Se encriptaron {$updated} registros de ciudadanos.\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR en la migración de encriptación: " . $e->getMessage() . "\n";
}
