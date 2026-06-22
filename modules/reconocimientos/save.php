<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_reconocimientos');

require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim($_POST['numero_acta'] ?? '') === '' || empty($_POST['reconocido_id']) || empty($_POST['reconocedor_id']) || trim($_POST['fecha_registro'] ?? '') === '') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        $numero_acta = mb_strtoupper(trim($_POST['numero_acta'] ?? ''), 'UTF-8');
        $reconocido_id = intval($_POST['reconocido_id'] ?? 0);
        $reconocedor_id = intval($_POST['reconocedor_id'] ?? 0);
        $fecha_registro = trim($_POST['fecha_registro'] ?? '');
        $usuario_registro = $_SESSION['user_id'];

        // Validar Estado Vital
        $stmtStatus = $pdo->prepare("SELECT id, nombre, apellido_paterno, estado_vital FROM ciudadanos WHERE id IN (?, ?) AND estado_vital = 'FINADO'");
        $stmtStatus->execute([$reconocido_id, $reconocedor_id]);
        $finados = $stmtStatus->fetchAll();
        if (count($finados) > 0) {
            $nombres = array_map(function($f) { return trim($f['nombre'] . ' ' . $f['apellido_paterno']); }, $finados);
            echo json_encode(['status' => 'error', 'message' => 'Operación denegada: ' . implode(', ', $nombres) . ' tiene estado vital FINADO.']);
            exit;
        }

        $sql = "INSERT INTO reconocimientos (numero_acta, reconocido_id, reconocedor_id, fecha_registro, usuario_registro) 
                         VALUES (:numero_acta, :reconocido_id, :reconocedor_id, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':numero_acta' => $numero_acta, 
            ':reconocido_id' => $reconocido_id, 
            ':reconocedor_id' => $reconocedor_id, 
            ':fecha_registro' => $fecha_registro, 
            ':usuario_registro' => $usuario_registro
        ]);

        if ($result) {
            \Core\Audit::log('INSERT', 'reconocimientos', 'Se registró un nuevo trámite/registro.');
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro.']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'El número de acta ingresado ya se encuentra registrado.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
