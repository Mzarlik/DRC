<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_divorcios');

require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim($_POST['numero_acta'] ?? '') === '' || empty($_POST['ciudadano_1_id']) || empty($_POST['ciudadano_2_id']) || trim($_POST['tipo_divorcio'] ?? '') === '' || trim($_POST['fecha_registro'] ?? '') === '') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        $numero_acta = mb_strtoupper(trim($_POST['numero_acta'] ?? ''), 'UTF-8');
        $ciudadano_1_id = intval($_POST['ciudadano_1_id'] ?? 0);
        $ciudadano_2_id = intval($_POST['ciudadano_2_id'] ?? 0);
        $tipo_divorcio = mb_strtoupper(trim($_POST['tipo_divorcio'] ?? ''), 'UTF-8');
        $fecha_registro = trim($_POST['fecha_registro'] ?? '');
        $usuario_registro = $_SESSION['user_id'];

        // Validar Estado Vital
        $stmtStatus = $pdo->prepare("SELECT id, nombre, apellido_paterno, estado_vital FROM ciudadanos WHERE id IN (?, ?) AND estado_vital = 'FINADO'");
        $stmtStatus->execute([$ciudadano_1_id, $ciudadano_2_id]);
        $finados = $stmtStatus->fetchAll();
        if (count($finados) > 0) {
            $nombres = array_map(function($f) { return trim($f['nombre'] . ' ' . $f['apellido_paterno']); }, $finados);
            echo json_encode(['status' => 'error', 'message' => 'Operación denegada: ' . implode(', ', $nombres) . ' tiene estado vital FINADO.']);
            exit;
        }

        $sql = "INSERT INTO divorcios (numero_acta, ciudadano_1_id, ciudadano_2_id, tipo_divorcio, fecha_registro, usuario_registro) 
                         VALUES (:numero_acta, :ciudadano_1_id, :ciudadano_2_id, :tipo_divorcio, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':numero_acta' => $numero_acta, 
            ':ciudadano_1_id' => $ciudadano_1_id, 
            ':ciudadano_2_id' => $ciudadano_2_id, 
            ':tipo_divorcio' => $tipo_divorcio, 
            ':fecha_registro' => $fecha_registro, 
            ':usuario_registro' => $usuario_registro
        ]);

        if ($result) {
            \Core\Audit::log('INSERT', 'divorcios', 'Se registró un nuevo trámite/registro.');
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
