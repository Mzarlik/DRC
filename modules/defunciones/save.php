<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_defunciones');
\Core\Auth::check();

// modules/defunciones/save.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $numero_acta = trim($_POST['numero_acta'] ?? '');
    $ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
    $fecha_defuncion = trim($_POST['fecha_defuncion'] ?? '');
    $fecha_registro = trim($_POST['fecha_registro'] ?? '');
    $causa_muerte = mb_strtoupper(trim($_POST['causa_muerte'] ?? ''), 'UTF-8');

    if (!$ciudadano_id || !$numero_acta || !$causa_muerte || !$fecha_defuncion || !$fecha_registro) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();
        
        // Iniciar transacción ya que se afectarán dos tablas (defunciones y ciudadanos)
        $pdo->beginTransaction();

        $sqlDefuncion = "INSERT INTO defunciones (numero_acta, ciudadano_id, fecha_defuncion, causa_muerte, fecha_registro) 
                         VALUES (:numero_acta, :ciudadano_id, :fecha_defuncion, :causa_muerte, :fecha_registro)";
        
        $stmtDefuncion = $pdo->prepare($sqlDefuncion);
        $stmtDefuncion->execute([
            ':numero_acta' => $numero_acta,
            ':ciudadano_id' => $ciudadano_id,
            ':fecha_defuncion' => $fecha_defuncion,
            ':causa_muerte' => $causa_muerte,
            ':fecha_registro' => $fecha_registro
        ]);

        // Actualizar el estado vital a FINADO
        $sqlEstado = "UPDATE ciudadanos SET estado_vital = 'FINADO' WHERE id = :ciudadano_id";
        $stmtEstado = $pdo->prepare($sqlEstado);
        $stmtEstado->execute([':ciudadano_id' => $ciudadano_id]);

        // Confirmar transacción
        $pdo->commit();

        \Core\Audit::log('INSERT', 'defunciones', 'Se registró un nuevo trámite/registro.');
        echo json_encode(['status' => 'success']);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'El número de acta ya existe o hubo un error en la validación.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error de integridad en la base de datos.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
