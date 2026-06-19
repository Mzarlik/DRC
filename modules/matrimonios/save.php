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

        $p_{substr(:numero_acta, 1)} = mb_strtoupper(trim($_POST['numero_acta'] ?? ''), 'UTF-8');
        $p_{substr(:contrayente_1_id, 1)} = intval($_POST['contrayente_1_id'] ?? 0);
        $p_{substr(:contrayente_2_id, 1)} = intval($_POST['contrayente_2_id'] ?? 0);
        $p_{substr(:regimen_patrimonial, 1)} = mb_strtoupper(trim($_POST['regimen_patrimonial'] ?? ''), 'UTF-8');
        $p_{substr(:fecha_registro, 1)} = trim($_POST['fecha_registro'] ?? '');
        $p_{substr(:usuario_registro, 1)} = $_SESSION['user_id'];

        $sql = "INSERT INTO matrimonios (numero_acta, contrayente_1_id, contrayente_2_id, regimen_patrimonial, fecha_registro, usuario_registro) 
                         VALUES (:numero_acta, :contrayente_1_id, :contrayente_2_id, :regimen_patrimonial, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([':numero_acta' => $p_{substr(:numero_acta, 1)}, ':contrayente_1_id' => $p_{substr(:contrayente_1_id, 1)}, ':contrayente_2_id' => $p_{substr(:contrayente_2_id, 1)}, ':regimen_patrimonial' => $p_{substr(:regimen_patrimonial, 1)}, ':fecha_registro' => $p_{substr(:fecha_registro, 1)}, ':usuario_registro' => $p_{substr(:usuario_registro, 1)}]);

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
