<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_tickets');
\Core\Auth::check();

// modules/peticiones/save.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!\Core\Auth::validateCSRF($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.']);
    exit;
}

$ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
$tipo_peticion = mb_strtoupper(trim($_POST['tipo_peticion'] ?? ''), 'UTF-8');
$descripcion = mb_strtoupper(trim($_POST['descripcion'] ?? ''), 'UTF-8');

if (!$ciudadano_id || !$tipo_peticion || !$descripcion) {
    echo json_encode(['status' => 'error', 'message' => 'Seleccione el ciudadano, el tipo de trámite y la descripción.']);
    exit;
}

// Generación de Folio de Seguimiento Transaccional: SEG-2026-00001
$folio = Database::generateFolio('seguimiento_' . date('Y'), 'SEG-' . date('Y') . '-', 5);

try {
    $pdo = Database::getConnection();

    $sql = "INSERT INTO peticiones (folio, ciudadano_id, tipo_peticion, descripcion, estatus, usuario_asignado) 
            VALUES (:folio, :ciudadano_id, :tipo_peticion, :descripcion, 'ABIERTA', :user_id)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':folio' => $folio,
        ':ciudadano_id' => $ciudadano_id,
        ':tipo_peticion' => $tipo_peticion,
        ':descripcion' => $descripcion,
        ':user_id' => $_SESSION['user_id'] ?? null
    ]);

    if ($result) {
        $id = (int)$pdo->lastInsertId();
        \Core\Auditoria::logAccion('Ventanilla de Seguimiento', 'CREAR', "Expediente de seguimiento aperturado. Folio: $folio, Tipo: $tipo_peticion, Ciudadano ID: $ciudadano_id");
        echo json_encode(['status' => 'success', 'folio' => $folio, 'id' => $id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al aperturar el expediente de seguimiento.']);
    }
} catch (\Throwable $e) {
    error_log('peticiones/save: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
