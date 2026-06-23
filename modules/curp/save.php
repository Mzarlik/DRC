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

        $ciudadano_id = intval($_POST['ciudadano_id'] ?? 0);
        $tipo_solicitud = mb_strtoupper(trim($_POST['tipo_solicitud'] ?? ''), 'UTF-8');
        $estatus = mb_strtoupper(trim($_POST['estatus'] ?? ''), 'UTF-8');
        $fecha_registro = trim($_POST['fecha_registro'] ?? '');
        $usuario_registro = $_SESSION['user_id'] ?? null;

        $sql = "INSERT INTO tramites_curp (ciudadano_id, tipo_solicitud, estatus, fecha_registro, usuario_registro) 
                         VALUES (:ciudadano_id, :tipo_solicitud, :estatus, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':ciudadano_id' => $ciudadano_id,
            ':tipo_solicitud' => $tipo_solicitud,
            ':estatus' => $estatus,
            ':fecha_registro' => $fecha_registro,
            ':usuario_registro' => $usuario_registro
        ]);

        if ($result) {
            \Core\Auditoria::logAccion('CURP', 'CREAR', "Se registró un trámite CURP ($tipo_solicitud) para Ciudadano ID: $ciudadano_id con estatus $estatus");
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
