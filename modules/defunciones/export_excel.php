<?php
// modules/defunciones/export_excel.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_defunciones');
\Core\Auth::check();
\Core\Auth::checkExport('Defunciones');

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Jobs.php';
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
    
    $search = $_POST['search'] ?? '';
    
    $payload = json_encode([
        'search' => $search
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO jobs (user_id, type, payload, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'export_defunciones',
        $payload,
        'pending'
    ]);
    
    $jobId = (int)$pdo->lastInsertId();
    \Core\Jobs::launchWorker();
    
    echo json_encode([
        'status' => 'success',
        'job_id' => $jobId,
        'message' => 'La exportación del registro de defunciones se está generando en segundo plano. Te notificaremos cuando esté listo.'
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al registrar el reporte. Intente de nuevo más tarde.'
    ]);
    exit;
}
