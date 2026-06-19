<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_curp');

require_once '../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ciudadano_id']) || trim($_POST['tipo_solicitud'] ?? '') === '' || trim($_POST['estatus'] ?? '') === '' || trim($_POST['fecha_registro'] ?? '') === '') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        $p_{substr(:ciudadano_id, 1)} = intval($_POST['ciudadano_id'] ?? 0);
        $p_{substr(:tipo_solicitud, 1)} = mb_strtoupper(trim($_POST['tipo_solicitud'] ?? ''), 'UTF-8');
        $p_{substr(:estatus, 1)} = mb_strtoupper(trim($_POST['estatus'] ?? ''), 'UTF-8');
        $p_{substr(:fecha_registro, 1)} = trim($_POST['fecha_registro'] ?? '');
        $p_{substr(:usuario_registro, 1)} = $_SESSION['user_id'];

        $sql = "INSERT INTO tramites_curp (ciudadano_id, tipo_solicitud, estatus, fecha_registro, usuario_registro) 
                         VALUES (:ciudadano_id, :tipo_solicitud, :estatus, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([':ciudadano_id' => $p_{substr(:ciudadano_id, 1)}, ':tipo_solicitud' => $p_{substr(:tipo_solicitud, 1)}, ':estatus' => $p_{substr(:estatus, 1)}, ':fecha_registro' => $p_{substr(:fecha_registro, 1)}, ':usuario_registro' => $p_{substr(:usuario_registro, 1)}]);

        if ($result) {
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
