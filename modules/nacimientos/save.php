<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_nacimientos');
\Core\Auth::check();

// modules/nacimientos/save.php
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
    $fecha_registro = trim($_POST['fecha_registro'] ?? '');
    $lugar_nacimiento = mb_strtoupper(trim($_POST['lugar_nacimiento'] ?? ''), 'UTF-8');
    
    $ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
    $padre_id = !empty($_POST['padre_id']) ? intval($_POST['padre_id']) : null;
    $madre_id = !empty($_POST['madre_id']) ? intval($_POST['madre_id']) : null;

    if (!$ciudadano_id || !$numero_acta || !$lugar_nacimiento || !$fecha_registro) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    // Regla de Negocio: La línea de acta se guarda como string.
    try {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO nacimientos (numero_acta, ciudadano_id, padre_id, madre_id, lugar_nacimiento, fecha_registro) 
                VALUES (:numero_acta, :ciudadano_id, :padre_id, :madre_id, :lugar_nacimiento, :fecha_registro)";
        
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            ':numero_acta' => $numero_acta,
            ':ciudadano_id' => $ciudadano_id,
            ':padre_id' => $padre_id,
            ':madre_id' => $madre_id,
            ':lugar_nacimiento' => $lugar_nacimiento,
            ':fecha_registro' => $fecha_registro
        ]);

        if ($result) {
            \Core\Audit::log('INSERT', 'nacimientos', 'Se registró un nuevo trámite/registro.');
        echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro.']);
        }

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'El número de acta ya existe o hubo un error de llaves foráneas.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error de integridad en la base de datos.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
