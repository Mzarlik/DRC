<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/foraneas/save.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $numero_acta = trim($_POST['numero_acta'] ?? '');
    $estado_origen = mb_strtoupper(trim($_POST['estado_origen'] ?? ''), 'UTF-8');
    $tipo_acta = trim($_POST['tipo_acta'] ?? '');
    $ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
    $fecha_recepcion = trim($_POST['fecha_recepcion'] ?? '');
    $observaciones = mb_strtoupper(trim($_POST['observaciones'] ?? ''), 'UTF-8');

    if (!$ciudadano_id || !$numero_acta || !$estado_origen || !$tipo_acta || !$fecha_recepcion) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO foraneas (numero_acta, estado_origen, tipo_acta, ciudadano_id, fecha_recepcion, observaciones) 
                VALUES (:numero_acta, :estado_origen, :tipo_acta, :ciudadano_id, :fecha_recepcion, :observaciones)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':numero_acta' => $numero_acta,
            ':estado_origen' => $estado_origen,
            ':tipo_acta' => $tipo_acta,
            ':ciudadano_id' => $ciudadano_id,
            ':fecha_recepcion' => $fecha_recepcion,
            ':observaciones' => $observaciones
        ]);

        if ($result) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de integridad en la base de datos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
