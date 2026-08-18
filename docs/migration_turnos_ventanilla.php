<?php
// docs/migration_turnos_ventanilla.php
// Turnos de atención, petición rápida de ventanilla, rol COORDINADOR y banderas
// de exportación/ventanilla.
//
// Uso: php docs/migration_turnos_ventanilla.php
// Idempotente: valida antes de cada ALTER/CREATE. Ejecutar UNA sola vez.

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

require_once __DIR__ . '/../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    // ==========================================================================
    // 1. ROL COORDINADOR + BANDERAS NUEVAS DE ACCESO
    // ==========================================================================
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col && strpos($col['Type'], 'COORDINADOR') === false) {
        $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('ADMIN','COORDINADOR','SUPERVISOR','OPERADOR') NOT NULL DEFAULT 'OPERADOR'");
        echo "Usuarios: rol COORDINADOR agregado al ENUM.\n";
    } else {
        echo "Usuarios: rol COORDINADOR ya existe.\n";
    }

    $nuevasBanderas = [
        // bandera => default (1 habilita para todo usuario nuevo)
        'permiso_exportar'             => 0, // Exportar a Excel (solo si no es coordinador)
        'permiso_peticiones_rapidas'   => 1, // Petición rápida de ventanilla
        'permiso_turnos'               => 1, // Turnos de atención
    ];
    foreach ($nuevasBanderas as $bandera => $default) {
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '$bandera'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN $bandera TINYINT(1) NOT NULL DEFAULT $default");
            echo "Usuarios: bandera $bandera agregada (default $default).\n";
        } else {
            echo "Usuarios: bandera $bandera ya existe.\n";
        }
    }

    // ==========================================================================
    // 2. TABLA peticiones_ventanilla (petición rápida)
    // ==========================================================================
    $stmt = $pdo->query("SHOW TABLES LIKE 'peticiones_ventanilla'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE TABLE peticiones_ventanilla (
            id INT AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(20) NOT NULL UNIQUE,
            ciudadano_id INT NOT NULL,
            tipo_peticion VARCHAR(50) NOT NULL,
            detalle VARCHAR(255) NULL,
            estatus ENUM('PENDIENTE','ENTREGADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
            usuario_registro INT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (ciudadano_id) REFERENCES ciudadanos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_registro) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE INDEX idx_pv_estatus ON peticiones_ventanilla (estatus)");
        $pdo->exec("CREATE INDEX idx_pv_fecha ON peticiones_ventanilla (creado_en)");
        echo "Tabla peticiones_ventanilla creada.\n";
    } else {
        echo "Tabla peticiones_ventanilla ya existe.\n";
    }

    // ==========================================================================
    // 3. TABLA turnos (sistema de turnos de ventanilla)
    // ==========================================================================
    $stmt = $pdo->query("SHOW TABLES LIKE 'turnos'");
    if (!$stmt->fetch()) {
        $pdo->exec("CREATE TABLE turnos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(20) NOT NULL UNIQUE,
            modulo_atencion VARCHAR(60) NOT NULL,
            ciudadano_nombre VARCHAR(250) NULL,
            estado ENUM('EN_ESPERA','ATENDIENDO','COMPLETADO','CANCELADO') NOT NULL DEFAULT 'EN_ESPERA',
            ventanilla VARCHAR(60) NULL,
            usuario_atendio INT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atendido_en DATETIME NULL,
            finalizado_en DATETIME NULL,
            FOREIGN KEY (usuario_atendio) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE INDEX idx_turnos_estado ON turnos (estado, creado_en)");
        echo "Tabla turnos creada.\n";
    } else {
        echo "Tabla turnos ya existe.\n";
    }

    // ==========================================================================
    // 4. CATÁLOGO tipo_peticion_ventanilla (ACTA FORÁNEA / CONSTANCIA)
    // ==========================================================================
    $stmt = $pdo->query("SELECT id FROM catalogos WHERE nombre_interno = 'tipo_peticion_ventanilla'");
    $catId = $stmt->fetchColumn();
    if (!$catId) {
        $pdo->exec("INSERT INTO catalogos (nombre_interno, descripcion) VALUES ('tipo_peticion_ventanilla', 'Tipo de Petición (Ventanilla)')");
        $catId = (int)$pdo->lastInsertId();
        echo "Catálogo tipo_peticion_ventanilla creado.\n";
    } else {
        echo "Catálogo tipo_peticion_ventanilla ya existe.\n";
    }
    $opciones = [
        ['ACTA_FORANEA', 'ACTA FORÁNEA', 1],
        ['CONSTANCIA', 'CONSTANCIA', 2],
    ];
    foreach ($opciones as $op) {
        $stmt = $pdo->prepare("SELECT id FROM catalogo_opciones WHERE catalogo_id = ? AND clave = ?");
        $stmt->execute([$catId, $op[0]]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO catalogo_opciones (catalogo_id, clave, valor, orden, activo) VALUES (?, ?, ?, ?, 1)")
                ->execute([$catId, $op[0], $op[1], $op[2]]);
            echo "Opción {$op[0]} agregada al catálogo.\n";
        }
    }

    // ==========================================================================
    // 5. CONFIGURACIÓN: prefijos de folio
    // ==========================================================================
    $conf = [
        'PREFIJO_PETICION_VENTANILLA' => 'VP-',
        'PREFIJO_TURNO' => 'VT-',
    ];
    foreach ($conf as $clave => $valor) {
        $stmt = $pdo->prepare("SELECT clave FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        if (!$stmt->fetchColumn()) {
            $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, '')")
                ->execute([$clave, $valor]);
            echo "Configuración $clave = $valor agregada.\n";
        }
    }

    // ==========================================================================
    // 6. USUARIOS EXISTENTES: coordinadores (ADMIN/SUPERVISOR) heredan
    //    permisos de exportación; todos obtienen ventanilla y turnos.
    // ==========================================================================
    $pdo->exec("UPDATE usuarios SET permiso_exportar = 1 WHERE rol IN ('ADMIN','SUPERVISOR') AND permiso_exportar = 0");
    $pdo->exec("UPDATE usuarios SET permiso_peticiones_rapidas = 1, permiso_turnos = 1 WHERE permiso_peticiones_rapidas = 0 OR permiso_turnos = 0");
    echo "Usuarios existentes actualizados con las nuevas banderas.\n";

    echo "\nMigración turnos/ventanilla completada con éxito.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
