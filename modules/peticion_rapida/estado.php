<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/estado.php
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
$estatus = strtoupper(trim($_POST['estatus'] ?? ''));

if ($id <= 0 || !in_array($estatus, ['ENTREGADO', 'CANCELADO'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT folio, tipo_peticion, estatus FROM peticiones_ventanilla WHERE id = ?");
    $stmt->execute([$id]);
    $pv = $stmt->fetch();

    if (!$pv) {
        echo json_encode(['status' => 'error', 'message' => 'La petición no existe o fue eliminada.']);
        exit;
    }

    if ($pv['estatus'] === 'ENTREGADO' && $estatus === 'ENTREGADO') {
        echo json_encode(['status' => 'error', 'message' => 'Esta petición ya fue entregada al solicitante.']);
        exit;
    }

    if ($pv['estatus'] === 'CANCELADO') {
        echo json_encode(['status' => 'error', 'message' => 'Esta petición se encuentra cancelada y no puede ser modificada.']);
        exit;
    }

    $pdo->prepare("UPDATE peticiones_ventanilla SET estatus = ?, actualizado_en = NOW() WHERE id = ?")->execute([$estatus, $id]);
    \Core\Auditoria::logAccion('Petición Rápida', 'EDITAR', "Petición {$pv['folio']} actualizada a estatus: $estatus.");

    $mensaje = ($estatus === 'ENTREGADO')
        ? '¡Petición marcada como entregada exitosamente! El trámite se completó en ventanilla.'
        : 'Petición cancelada correctamente.';

    echo json_encode(['status' => 'success', 'message' => $mensaje]);
} catch (\Throwable $e) {
    error_log('peticion_rapida/estado: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
