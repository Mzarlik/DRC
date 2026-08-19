<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/update.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;
use Core\Services\PeticionRapidaService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!\Core\Auth::validateCSRF($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de petición inválido.']);
    exit;
}

// Validar datos
$validacion = PeticionRapidaService::validar($_POST);
if (!$validacion['valido']) {
    echo json_encode(['status' => 'error', 'message' => $validacion['error']]);
    exit;
}

$data = $validacion['data'];

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT folio FROM peticiones_ventanilla WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $folio = $stmt->fetchColumn();

    if (!$folio) {
        echo json_encode(['status' => 'error', 'message' => 'La petición no existe o fue eliminada.']);
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE peticiones_ventanilla SET 
        solicitante_nombre = ?, 
        solicitante_curp = ?, 
        solicitante_telefono = ?, 
        tipo_peticion = ?, 
        detalle = ?, 
        estatus = ?, 
        usuario_modifico = ? 
        WHERE id = ?");

    $stmtUpdate->execute([
        $data['solicitante_nombre'],
        $data['solicitante_curp'],
        $data['solicitante_telefono'],
        $data['tipo_peticion'],
        $data['detalle'],
        $data['estatus'],
        $_SESSION['user_id'] ?? null,
        $id
    ]);

    \Core\Auditoria::logAccion('Petición Rápida', 'EDITAR', "Petición actualizada. Folio: $folio, Estatus: {$data['estatus']}, Solicitante: {$data['solicitante_nombre']}");
    echo json_encode(['status' => 'success', 'message' => "Petición $folio actualizada correctamente."]);
} catch (\Throwable $e) {
    error_log('peticion_rapida/update: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
