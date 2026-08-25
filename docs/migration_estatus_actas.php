<?php
// docs/migration_estatus_actas.php
// Columna de estatus del ciclo de vida del acta (REGISTRADO/CANCELADO) en las 6 tablas
// de actas registrales, para seguimiento visual y corrección de errores operativos.
//
// Uso: php docs/migration_estatus_actas.php
// Idempotente: valida antes de cada ALTER. Ejecutar UNA sola vez.

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

require_once __DIR__ . '/../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    $tablasActas = [
        'nacimientos', 'matrimonios', 'divorcios',
        'defunciones', 'inscripciones', 'reconocimientos',
    ];

    foreach ($tablasActas as $tabla) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabla` LIKE 'estatus'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `$tabla` ADD COLUMN estatus ENUM('REGISTRADO','CANCELADO') NOT NULL DEFAULT 'REGISTRADO'");
            echo "Estatus: columna estatus agregada a $tabla (default REGISTRADO).\n";
        } else {
            echo "Estatus: columna estatus ya existe en $tabla.\n";
        }

        $nombreIndice = 'idx_' . $tabla . '_estatus';
        $stmt = $pdo->query("SHOW INDEX FROM `$tabla` WHERE Key_name = '$nombreIndice'");
        if (!$stmt->fetch()) {
            $pdo->exec("CREATE INDEX `$nombreIndice` ON `$tabla` (estatus)");
            echo "Índice $nombreIndice creado en $tabla.\n";
        } else {
            echo "Índice $nombreIndice ya existe en $tabla.\n";
        }
    }

    echo "Migración completada con éxito.\n";

} catch (Exception $e) {
    echo "Error ejecutando la migración: " . $e->getMessage() . "\n";
    exit(1);
}
