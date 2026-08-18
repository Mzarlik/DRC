<?php
// modules/reportes/export_excel.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::check();
\Core\Auth::checkExport('Reportes Cruzados');

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
    
    // Obtener filtros del reporte
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $modulo = $_POST['modulo'] ?? '';
    $estatus = $_POST['estatus'] ?? '';
    $operador_id = $_POST['operador_id'] ?? '';
    
    $payload = json_encode([
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'modulo' => $modulo,
        'estatus' => $estatus,
        'operador_id' => $operador_id
    ]);
    
    // Registrar el trabajo en segundo plano
    $stmt = $pdo->prepare("INSERT INTO jobs (user_id, type, payload, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'export_general_report',
        $payload,
        'pending'
    ]);
    
    \Core\Jobs::launchWorker();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'El reporte general cruzado se está generando en segundo plano. Te notificaremos cuando esté listo para su descarga.'
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al programar la generación del reporte. Intente de nuevo más tarde.'
    ]);
    exit;
}
