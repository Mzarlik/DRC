<?php
// docs/migration_pv_curp_bindex.php
// Migración: agrega solicitante_curp_bindex a peticiones_ventanilla y rellena
// el blind index + cifra las CURP que estén en texto plano (legado).
//
// EJECUTAR SOLO POR CLI:
//   c:\xampp\php\php.exe docs\migration_pv_curp_bindex.php
//
// Es idempotente: puede ejecutarse varias veces sin duplicar cambios.

if (php_sapi_name() !== 'cli') {
    die("Esta migración solo debe ejecutarse desde la consola CLI.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Encryption.php';

use Core\Database;
use Core\Encryption;

try {
    $pdo = Database::getConnection();

    // 1. Agregar columna de blind index si no existe
    $stmtCol = $pdo->query("SHOW COLUMNS FROM peticiones_ventanilla LIKE 'solicitante_curp_bindex'");
    if (!$stmtCol->fetch()) {
        $pdo->exec("ALTER TABLE peticiones_ventanilla ADD COLUMN solicitante_curp_bindex VARCHAR(64) NULL AFTER solicitante_curp");
        echo "Columna 'solicitante_curp_bindex' agregada a peticiones_ventanilla.\n";
    } else {
        echo "La columna 'solicitante_curp_bindex' ya existe.\n";
    }

    // 2. Crear índice de búsqueda si no existe
    $stmtIdx = $pdo->query("SHOW INDEX FROM peticiones_ventanilla WHERE Key_name = 'idx_pv_curp_bindex'");
    if (!$stmtIdx->fetch()) {
        $pdo->exec("CREATE INDEX idx_pv_curp_bindex ON peticiones_ventanilla(solicitante_curp_bindex)");
        echo "Índice 'idx_pv_curp_bindex' creado.\n";
    } else {
        echo "El índice 'idx_pv_curp_bindex' ya existe.\n";
    }

    // 3. Cifrar CURP en texto plano (legado) y rellenar blind index faltantes
    $stmt = $pdo->query("SELECT id, solicitante_curp, solicitante_curp_bindex FROM peticiones_ventanilla WHERE solicitante_curp IS NOT NULL AND solicitante_curp != ''");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cifradas = 0;
    $reindizadas = 0;
    $update = $pdo->prepare("UPDATE peticiones_ventanilla SET solicitante_curp = ?, solicitante_curp_bindex = ? WHERE id = ?");

    foreach ($rows as $row) {
        $valor = $row['solicitante_curp'];
        $bindexActual = $row['solicitante_curp_bindex'];

        // Determinar texto plano: decrypt() devuelve el mismo valor si no es cifrado válido
        $plano = Encryption::decrypt($valor);
        $esCurpValida = is_string($plano) && preg_match('/^[A-Z0-9]{18}$/', trim($plano)) === 1;

        if ($esCurpValida) {
            $plano = trim($plano);
            $nuevoCifrado = ($plano === $valor) ? Encryption::encrypt($plano) : $valor;
            $nuevoBindex = Encryption::getBlindIndex($plano);

            if ($nuevoCifrado !== $valor || $nuevoBindex !== $bindexActual) {
                $update->execute([$nuevoCifrado, $nuevoBindex, $row['id']]);
                if ($nuevoCifrado !== $valor) {
                    $cifradas++;
                }
                if ($nuevoBindex !== $bindexActual) {
                    $reindizadas++;
                }
            }
        } elseif ($bindexActual === null) {
            // Valor almacenado que no es CURP legible ni descifrable: dejar la columna vacía
            // para no romper búsquedas; conservar el dato original intacto.
            echo "  Aviso: fila ID {$row['id']} tiene una CURP no recuperable; se omite.\n";
        }
    }

    echo "Migración completada. CURP cifradas: $cifradas. Blind index actualizados: $reindizadas.\n";

} catch (Exception $e) {
    echo "Error ejecutando la migración: " . $e->getMessage() . "\n";
    exit(1);
}
