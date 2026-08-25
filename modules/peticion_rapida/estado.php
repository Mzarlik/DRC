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
$accion = strtoupper(trim($_POST['accion'] ?? ''));
$motivo = mb_strtoupper(trim($_POST['motivo'] ?? ''), 'UTF-8');

// Compatibilidad: el modal de seguimiento envía accion=REACTIVAR
if ($accion === 'REACTIVAR') {
    $estatus = 'PENDIENTE';
}

if ($id <= 0 || !in_array($estatus, ['ENTREGADO', 'CANCELADO', 'PENDIENTE'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
    exit;
}

$es_reactivacion = ($estatus === 'PENDIENTE');
if ($es_reactivacion && mb_strlen($motivo) < 5) {
    echo json_encode(['status' => 'error', 'message' => 'El motivo es obligatorio (mínimo 5 caracteres) para reactivar la petición.']);
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

    if ($pv['estatus'] === $estatus) {
        echo json_encode(['status' => 'error', 'message' => "Esta petición ya se encuentra en estatus $estatus."]);
        exit;
    }

    if (!$es_reactivacion) {
        if ($pv['estatus'] === 'ENTREGADO') {
            echo json_encode(['status' => 'error', 'message' => 'Esta petición ya fue entregada al solicitante. Use la opción de reactivar si fue un error.']);
            exit;
        }
        if ($pv['estatus'] === 'CANCELADO') {
            echo json_encode(['status' => 'error', 'message' => 'Esta petición se encuentra cancelada. Use la opción de reactivar si fue un error.']);
            exit;
        }
    }

    $pdo->prepare("UPDATE peticiones_ventanilla SET estatus = ?, actualizado_en = NOW() WHERE id = ?")->execute([$estatus, $id]);

    if ($es_reactivacion) {
        \Core\Auditoria::logAccion('Petición Rápida', 'REACTIVAR', "Petición {$pv['folio']} REACTIVADA a PENDIENTE (estatus previo: {$pv['estatus']}). Motivo: $motivo");
        echo json_encode(['status' => 'success', 'message' => 'Petición reactivada a PENDIENTE correctamente.']);
        exit;
    }

    \Core\Auditoria::logAccion('Petición Rápida', 'EDITAR', "Petición {$pv['folio']} actualizada a estatus: $estatus.");

    $mensaje = ($estatus === 'ENTREGADO')
        ? '¡Petición marcada como entregada exitosamente! El trámite se completó en ventanilla.'
        : 'Petición cancelada correctamente.';

    echo json_encode(['status' => 'success', 'message' => $mensaje]);
} catch (\Throwable $e) {
    error_log('peticion_rapida/estado: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
