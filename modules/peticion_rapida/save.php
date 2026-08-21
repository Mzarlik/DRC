<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/save.php
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

// Validar campos usando el servicio de peticiones
$validacion = PeticionRapidaService::validar($_POST);
if (!$validacion['valido']) {
    echo json_encode(['status' => 'error', 'message' => $validacion['error']]);
    exit;
}

$data = $validacion['data'];
$ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;

try {
    $pdo = Database::getConnection();

    // Detectar si ya existe la columna de blind index (migración opcional)
    $hasBindex = false;
    try {
        $pdo->query("SELECT solicitante_curp_bindex FROM peticiones_ventanilla LIMIT 0");
        $hasBindex = true;
    } catch (\Throwable $e) {
        $hasBindex = false;
    }

    // Generar folio simplificado: ej. FOR-260819-001, BSI-260819-001
    $folio = PeticionRapidaService::generarFolioSimplificado($data['tipo_peticion']);

    if ($hasBindex) {
        $stmt = $pdo->prepare("INSERT INTO peticiones_ventanilla
            (folio, ciudadano_id, solicitante_nombre, solicitante_curp, solicitante_curp_bindex, solicitante_telefono, tipo_peticion, detalle, estatus, usuario_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', ?)");

        $result = $stmt->execute([
            $folio,
            $ciudadano_id,
            $data['solicitante_nombre'],
            $data['solicitante_curp'],
            $data['solicitante_curp_bindex'],
            $data['solicitante_telefono'],
            $data['tipo_peticion'],
            $data['detalle'],
            $_SESSION['user_id'] ?? null
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO peticiones_ventanilla
            (folio, ciudadano_id, solicitante_nombre, solicitante_curp, solicitante_telefono, tipo_peticion, detalle, estatus, usuario_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', ?)");

        $result = $stmt->execute([
            $folio,
            $ciudadano_id,
            $data['solicitante_nombre'],
            $data['solicitante_curp'],
            $data['solicitante_telefono'],
            $data['tipo_peticion'],
            $data['detalle'],
            $_SESSION['user_id'] ?? null
        ]);
    }

    if ($result) {
        $id = (int)$pdo->lastInsertId();
        \Core\Auditoria::logAccion('Petición Rápida', 'CREAR', "Petición registrada. Folio: $folio, Solicitante: {$data['solicitante_nombre']}, Trámite: {$data['tipo_peticion']}");
        echo json_encode(['status' => 'success', 'folio' => $folio, 'id' => $id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar la petición en la base de datos.']);
    }
} catch (\Throwable $e) {
    error_log('peticion_rapida/save: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e)]);
}
