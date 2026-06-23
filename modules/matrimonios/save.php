<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_matrimonios');

require_once '../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim($_POST['numero_acta'] ?? '') === '' || empty($_POST['contrayente_1_id']) || empty($_POST['contrayente_2_id']) || trim($_POST['regimen_patrimonial'] ?? '') === '' || trim($_POST['fecha_registro'] ?? '') === '') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        $numero_acta = mb_strtoupper(trim($_POST['numero_acta'] ?? ''), 'UTF-8');
        $contrayente_1_id = intval($_POST['contrayente_1_id'] ?? 0);
        $contrayente_2_id = intval($_POST['contrayente_2_id'] ?? 0);
        $regimen_patrimonial = mb_strtoupper(trim($_POST['regimen_patrimonial'] ?? ''), 'UTF-8');
        $fecha_registro = trim($_POST['fecha_registro'] ?? '');
        $usuario_registro = $_SESSION['user_id'];

        // Validar Estado Vital
        $stmtStatus = $pdo->prepare("SELECT id, nombre, apellido_paterno, estado_vital FROM ciudadanos WHERE id IN (?, ?) AND estado_vital = 'FINADO'");
        $stmtStatus->execute([$contrayente_1_id, $contrayente_2_id]);
        $finados = $stmtStatus->fetchAll();
        if (count($finados) > 0) {
            $nombres = array_map(function($f) { return trim($f['nombre'] . ' ' . $f['apellido_paterno']); }, $finados);
            echo json_encode(['status' => 'error', 'message' => 'Operación denegada: ' . implode(', ', $nombres) . ' tiene estado vital FINADO.']);
            exit;
        }

        $sql = "INSERT INTO matrimonios (numero_acta, contrayente_1_id, contrayente_2_id, regimen_patrimonial, fecha_registro, usuario_registro) 
                         VALUES (:numero_acta, :contrayente_1_id, :contrayente_2_id, :regimen_patrimonial, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':numero_acta' => $numero_acta, 
            ':contrayente_1_id' => $contrayente_1_id, 
            ':contrayente_2_id' => $contrayente_2_id, 
            ':regimen_patrimonial' => $regimen_patrimonial, 
            ':fecha_registro' => $fecha_registro, 
            ':usuario_registro' => $usuario_registro
        ]);

        if ($result) {
            \Core\Auditoria::logAccion('Matrimonios', 'CREAR', "Se registró un matrimonio. Acta: $numero_acta, Contrayente 1 ID: $contrayente_1_id, Contrayente 2 ID: $contrayente_2_id, Régimen: $regimen_patrimonial");
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
