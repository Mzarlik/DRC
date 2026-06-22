<?php
// modules/inexistencias/export_excel.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    
    $tipo = $_GET['tipo'] ?? '';
    
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
    
    // Disparar el Worker asíncronamente en Windows sin bloquear la petición web
    $workerPath = escapeshellarg(dirname(dirname(__DIR__)) . '/core/Worker.php');
    pclose(popen("start /B c:\\xampp\\php\\php.exe $workerPath > NUL 2>&1", "r"));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'El reporte de inexistencias se está generando en segundo plano. Te notificaremos cuando esté listo.'
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al registrar el reporte asíncrono: ' . $e->getMessage()
    ]);
    exit;
}
