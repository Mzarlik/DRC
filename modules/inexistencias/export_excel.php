<?php
// modules/inexistencias/export_excel.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');
\Core\Auth::check();

require_once '../../core/Database.php';
require_once '../../core/Jobs.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no soportado.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!\Core\Auth::validateCSRF($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    $tipo = $_POST['tipo'] ?? '';
    
    $payload = json_encode([
        'tipo' => $tipo
    ]);
    
    // Registrar el job en estatus 'pending'
    $stmt = $pdo->prepare("INSERT INTO jobs (user_id, type, payload, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'export_inexistencias',
        $payload,
        'pending'
    ]);
    
    \Core\Jobs::launchWorker();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'El reporte de inexistencias se está generando en segundo plano. Te notificaremos cuando esté listo.'
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al registrar el reporte. Intente de nuevo más tarde.'
    ]);
    exit;
}
