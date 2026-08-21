<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_turnos');
\Core\Auth::check();

// modules/turnos/create.php
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

$modulo_atencion = mb_strtoupper(trim($_POST['modulo_atencion'] ?? ''), 'UTF-8');
$ciudadano_nombre = mb_strtoupper(trim($_POST['ciudadano_nombre'] ?? ''), 'UTF-8');

if (!$modulo_atencion) {
    echo json_encode(['status' => 'error', 'message' => 'El módulo de atención es obligatorio.']);
    exit;
}
if (mb_strlen($modulo_atencion) > 60) {
    echo json_encode(['status' => 'error', 'message' => 'El módulo de atención no puede exceder 60 caracteres.']);
    exit;
}
if (mb_strlen($ciudadano_nombre) > 250) {
    echo json_encode(['status' => 'error', 'message' => 'El nombre no puede exceder 250 caracteres.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    $prefijo = 'VT-';
    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'PREFIJO_TURNO'");
    $conf = $stmt->fetchColumn();
    if ($conf !== false) {
        $prefijo = $conf;
    }
    $prefijo = strtoupper($prefijo);

    $folio = Database::generateFolio('turno_' . date('Y'), $prefijo . date('Y') . '-');

    $stmt = $pdo->prepare("INSERT INTO turnos (folio, modulo_atencion, ciudadano_nombre) VALUES (?, ?, ?)");
    $result = $stmt->execute([$folio, $modulo_atencion, $ciudadano_nombre ?: null]);

    if ($result) {
        $id = (int)$pdo->lastInsertId();
        \Core\Auditoria::logAccion('Turnos', 'CREAR', "Turno generado. Folio: $folio, Módulo: $modulo_atencion" . ($ciudadano_nombre ? ", Ciudadano: $ciudadano_nombre" : ''));
        echo json_encode(['status' => 'success', 'folio' => $folio, 'id' => $id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al generar el turno.']);
    }
} catch (\Throwable $e) {
    error_log('turnos/crear: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
