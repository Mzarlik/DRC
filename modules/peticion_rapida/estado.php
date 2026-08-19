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
    $stmt = $pdo->prepare("SELECT folio, tipo_peticion FROM peticiones_ventanilla WHERE id = ? AND estatus = 'PENDIENTE'");
    $stmt->execute([$id]);
    $pv = $stmt->fetch();

    if (!$pv) {
        echo json_encode(['status' => 'error', 'message' => 'La petición no existe o ya fue finalizada.']);
        exit;
    }

    $pdo->prepare("UPDATE peticiones_ventanilla SET estatus = ? WHERE id = ?")->execute([$estatus, $id]);
    \Core\Auditoria::logAccion('Petición Rápida', 'EDITAR', "Petición {$pv['folio']} marcada como $estatus.");

    $mensaje = ($estatus === 'ENTREGADO')
        ? 'Petición marcada como entregada. La constancia/acta fue proporcionada al ciudadano.'
        : 'Petición cancelada.';

    echo json_encode(['status' => 'success', 'message' => $mensaje]);
} catch (\Throwable $e) {
    error_log('peticion_rapida/estado: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
