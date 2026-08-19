<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/delete.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!\Core\Auth::validateCSRF($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de petición inválido.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT folio, solicitante_nombre FROM peticiones_ventanilla WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        echo json_encode(['status' => 'error', 'message' => 'La petición no existe o ya fue eliminada.']);
        exit;
    }

    $stmtDel = $pdo->prepare("UPDATE peticiones_ventanilla SET deleted_at = NOW(), deleted_by = ? WHERE id = ?");
    $stmtDel->execute([$_SESSION['user_id'] ?? null, $id]);

    \Core\Auditoria::logAccion('Petición Rápida', 'ELIMINAR', "Petición eliminada (Soft Delete). Folio: {$pet['folio']}, Solicitante: {$pet['solicitante_nombre']}");
    echo json_encode(['status' => 'success', 'message' => "Petición {$pet['folio']} eliminada correctamente."]);
} catch (\Throwable $e) {
    error_log('peticion_rapida/delete: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
