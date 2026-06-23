<?php
// modules/defunciones/export_excel.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_defunciones');
\Core\Auth::check();

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    
    $search = $_GET['search'] ?? '';
    
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
    
    $workerPath = escapeshellarg(dirname(dirname(__DIR__)) . '/core/Worker.php');
    pclose(popen("start /B c:\\xampp\\php\\php.exe $workerPath > NUL 2>&1", "r"));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'La exportación del registro de defunciones se está generando en segundo plano. Te notificaremos cuando esté listo.'
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al registrar el reporte: ' . $e->getMessage()
    ]);
    exit;
}
