<?php
// modules/reportes/export_excel.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::check();

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    
    // Obtener filtros del reporte
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $modulo = $_GET['modulo'] ?? '';
    $estatus = $_GET['estatus'] ?? '';
    $operador_id = $_GET['operador_id'] ?? '';
    
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
    
    // Disparar el Worker asíncronamente en segundo plano
    $workerPath = escapeshellarg(dirname(dirname(__DIR__)) . '/core/Worker.php');
    pclose(popen("start /B c:\\xampp\\php\\php.exe $workerPath > NUL 2>&1", "r"));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'El reporte general cruzado se está generando en segundo plano. Te notificaremos cuando esté listo para su descarga.'
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al programar la generación del reporte: ' . $e->getMessage()
    ]);
    exit;
}
