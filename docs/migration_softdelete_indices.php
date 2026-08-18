<?php
// docs/migration_softdelete_indices.php
// Soft-delete estándar (deleted_at/deleted_by) + índices complementarios.
//
// Uso: php docs/migration_softdelete_indices.php
// Idempotente: valida antes de cada ALTER/CREATE. Ejecutar UNA sola vez.

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

require_once __DIR__ . '/../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    // ==========================================================================
    // 1. SOFT-DELETE: columna deleted_at / deleted_by en tablas de negocio
    // ==========================================================================
    $tablasSoftDelete = [
        'ciudadanos', 'nacimientos', 'matrimonios', 'divorcios', 'defunciones',
        'inscripciones', 'reconocimientos', 'foraneas', 'peticiones', 'inexistencias',
        'tramites_curp',
    ];

    foreach ($tablasSoftDelete as $tabla) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabla` LIKE 'deleted_at'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `$tabla` ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
            echo "Soft-delete: columna deleted_at agregada a $tabla.\n";
        } else {
            echo "Soft-delete: deleted_at ya existe en $tabla.\n";
        }
        $stmtBy = $pdo->query("SHOW COLUMNS FROM `$tabla` LIKE 'deleted_by'");
        if (!$stmtBy->fetch()) {
            $pdo->exec("ALTER TABLE `$tabla` ADD COLUMN deleted_by INT NULL DEFAULT NULL");
            echo "Soft-delete: columna deleted_by agregada a $tabla.\n";
        }
    }

    // Consistencia: ciudadanos dados de baja con estado = 0 deben tener deleted_at
    $pdo->exec("UPDATE ciudadanos SET deleted_at = NOW() WHERE estado = 0 AND deleted_at IS NULL");
    echo "Soft-delete: bajas existentes (estado = 0) sincronizadas con deleted_at.\n";

    // ==========================================================================
    // 2. ÍNDICES COMPLEMENTARIOS (búsquedas y listados de alta demanda)
    // ==========================================================================
    $indices = [
        // tabla => [nombre_índice => columna(s)]
        'nacimientos'     => ['idx_nacimientos_ciudadano'  => 'ciudadano_id',
                              'idx_nacimientos_fecha'      => 'fecha_registro',
                              'idx_nacimientos_usuario'    => 'usuario_registro'],
        'defunciones'     => ['idx_defunciones_ciudadano'  => 'ciudadano_id',
                              'idx_defunciones_fecha'      => 'fecha_registro'],
        'matrimonios'     => ['idx_matrimonios_c1'         => 'contrayente_1_id',
                              'idx_matrimonios_c2'         => 'contrayente_2_id',
                              'idx_matrimonios_fecha'      => 'fecha_registro'],
        'divorcios'       => ['idx_divorcios_c1'           => 'ciudadano_1_id',
                              'idx_divorcios_c2'           => 'ciudadano_2_id'],
        'reconocimientos' => ['idx_reconocimientos_rdo'    => 'reconocido_id',
                              'idx_reconocimientos_rdor'   => 'reconocedor_id'],
        'inscripciones'   => ['idx_inscripciones_ciudadano'=> 'ciudadano_id'],
        'foraneas'        => ['idx_foraneas_ciudadano'     => 'ciudadano_id',
                              'idx_foraneas_fecha'         => 'fecha_recepcion'],
        'peticiones'      => ['idx_peticiones_ciudadano'   => 'ciudadano_id',
                              'idx_peticiones_estatus'     => 'estatus'],
        'inexistencias'   => ['idx_inexistencias_fecha'    => 'fecha_tramite'],
        'tramites_curp'   => ['idx_curp_ciudadano'         => 'ciudadano_id'],
        'jobs'            => ['idx_jobs_status'            => 'status'],
        'auditoria_logs'  => ['idx_auditoria_usuario'      => 'usuario_id',
                              'idx_auditoria_fecha'        => 'fecha_hora'],
        'error_logs'      => ['idx_errores_fecha'          => 'fecha_hora'],
    ];

    foreach ($indices as $tabla => $defs) {
        foreach ($defs as $nombre => $columna) {
            $stmt = $pdo->query("SHOW INDEX FROM `$tabla` WHERE Key_name = '$nombre'");
            if (!$stmt->fetch()) {
                $pdo->exec("CREATE INDEX `$nombre` ON `$tabla` ($columna)");
                echo "Índice $nombre creado en $tabla.\n";
            } else {
                echo "Índice $nombre ya existe en $tabla.\n";
            }
        }
    }

    echo "Migración completada con éxito.\n";

} catch (Exception $e) {
    echo "Error ejecutando la migración: " . $e->getMessage() . "\n";
    exit(1);
}