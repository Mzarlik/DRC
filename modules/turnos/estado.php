<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_turnos');
\Core\Auth::check();

// modules/turnos/estado.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
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
$estado = strtoupper(trim($_POST['estado'] ?? ''));

if ($id <= 0 || !in_array($estado, ['ATENDIENDO', 'COMPLETADO', 'CANCELADO'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT folio, modulo_atencion, estado FROM turnos WHERE id = ?");
    $stmt->execute([$id]);
    $turno = $stmt->fetch();

    if (!$turno) {
        echo json_encode(['status' => 'error', 'message' => 'El turno no existe.']);
        exit;
    }

    if ($estado === 'ATENDIENDO' && $turno['estado'] !== 'EN_ESPERA') {
        echo json_encode(['status' => 'error', 'message' => 'Solo se puede atender un turno en espera.']);
        exit;
    }
    if ($estado === 'COMPLETADO' && $turno['estado'] !== 'ATENDIENDO') {
        echo json_encode(['status' => 'error', 'message' => 'Solo se puede finalizar un turno en atención.']);
        exit;
    }
    if ($estado === 'CANCELADO' && !in_array($turno['estado'], ['EN_ESPERA', 'ATENDIENDO'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'El turno ya fue finalizado.']);
        exit;
    }

    $ventanilla = null;
    if ($estado === 'ATENDIENDO') {
        $ventanilla = mb_strtoupper(trim($_POST['ventanilla'] ?? ''), 'UTF-8') ?: (\Core\Auth::getUserName() ?? null);
        $pdo->prepare("UPDATE turnos SET estado = 'ATENDIENDO', ventanilla = ?, usuario_atendio = ?, atendido_en = NOW() WHERE id = ?")
            ->execute([$ventanilla, $_SESSION['user_id'] ?? null, $id]);
    } elseif ($estado === 'COMPLETADO') {
        $pdo->prepare("UPDATE turnos SET estado = 'COMPLETADO', finalizado_en = NOW() WHERE id = ?")->execute([$id]);
    } else {
        $pdo->prepare("UPDATE turnos SET estado = 'CANCELADO', finalizado_en = NOW() WHERE id = ?")->execute([$id]);
    }

    \Core\Auditoria::logAccion('Turnos', 'EDITAR', "Turno {$turno['folio']} ($estado)" . ($ventanilla ? " en ventanilla $ventanilla" : '') . ".");

    $mensaje = [
        'ATENDIENDO' => 'Turno en atención.',
        'COMPLETADO' => 'Turno finalizado.',
        'CANCELADO' => 'Turno cancelado.',
    ][$estado];

    echo json_encode(['status' => 'success', 'message' => $mensaje]);
} catch (\Throwable $e) {
    error_log('turnos/estado: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
