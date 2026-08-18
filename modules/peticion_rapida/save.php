<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/save.php
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

$ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
$tipo_peticion = strtoupper(trim($_POST['tipo_peticion'] ?? ''));
$detalle = mb_strtoupper(trim($_POST['detalle'] ?? ''), 'UTF-8');

if (!$ciudadano_id || !$tipo_peticion || !$detalle) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
    exit;
}

if (!in_array($tipo_peticion, ['ACTA_FORANEA', 'CONSTANCIA'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo de petición no válido.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    $prefijo = 'VP-';
    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'PREFIJO_PETICION_VENTANILLA'");
    $conf = $stmt->fetchColumn();
    if ($conf !== false) {
        $prefijo = $conf;
    }
    $prefijo = strtoupper($prefijo);

    $folio = Database::generateFolio('peticion_ventanilla_' . date('Y'), $prefijo . date('Y') . '-');

    $stmt = $pdo->prepare("INSERT INTO peticiones_ventanilla (folio, ciudadano_id, tipo_peticion, detalle, usuario_registro) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$folio, $ciudadano_id, $tipo_peticion, $detalle, $_SESSION['user_id'] ?? null]);

    if ($result) {
        $id = (int)$pdo->lastInsertId();
        \Core\Auditoria::logAccion('Petición Rápida', 'CREAR', "Petición de ventanilla registrada. Folio: $folio, Tipo: $tipo_peticion, Ciudadano ID: $ciudadano_id");
        echo json_encode(['status' => 'success', 'folio' => $folio, 'id' => $id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar la petición.']);
    }
} catch (\Throwable $e) {
    error_log('peticion_rapida/save: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
