<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/peticiones/save.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
    $tipo_peticion = trim($_POST['tipo_peticion'] ?? '');
    $descripcion = mb_strtoupper(trim($_POST['descripcion'] ?? ''), 'UTF-8');

    if (!$ciudadano_id || !$tipo_peticion || !$descripcion) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    // Generación de Folio: TK-AÑO-RANDOM
    $folio = 'TK-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

    try {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO peticiones (folio, ciudadano_id, tipo_peticion, descripcion) 
                VALUES (:folio, :ciudadano_id, :tipo_peticion, :descripcion)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':folio' => $folio,
            ':ciudadano_id' => $ciudadano_id,
            ':tipo_peticion' => $tipo_peticion,
            ':descripcion' => $descripcion
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'folio' => $folio]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al crear el ticket.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de integridad en la base de datos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
