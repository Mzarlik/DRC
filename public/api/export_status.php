<?php
// public/api/export_status.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::check();

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Jobs.php';
use Core\Database;

$jobId = (int)($_GET['job_id'] ?? 0);
if ($jobId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Job ID inválido.']);
    exit;
}

try {
    $pdo = Database::getReadConnection();
    $stmt = $pdo->prepare("SELECT id, user_id, type, status, file_path, error_message FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo json_encode(['status' => 'error', 'message' => 'Trabajo de exportación no encontrado.']);
        exit;
    }

    $isOwner = (int)$job['user_id'] === (int)($_SESSION['user_id'] ?? 0);
    $isAdmin = in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'SUPERVISOR', 'COORDINADOR'], true) || \Core\Auth::canExportar();
    if (!$isOwner && !$isAdmin) {
        echo json_encode(['status' => 'error', 'message' => 'No autorizado para acceder a esta exportación.']);
        exit;
    }

    if ($job['status'] === 'completed' && !empty($job['file_path'])) {
        $fileName = basename($job['file_path']);
        $downloadUrl = '/DRC/public/api/download_export.php?file=' . urlencode($fileName);
        echo json_encode([
            'status' => 'completed',
            'download_url' => $downloadUrl,
            'file_name' => $fileName
        ]);
        exit;
    }

    if ($job['status'] === 'failed') {
        echo json_encode([
            'status' => 'error',
            'message' => $job['error_message'] ?: 'Ocurrió un error al generar el archivo Excel.'
        ]);
        exit;
    }

    // Si aún está pendiente, re-asegurar lanzamiento del worker
    \Core\Jobs::launchWorker();

    echo json_encode(['status' => 'processing']);
    exit;

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar estado de la exportación.']);
    exit;
}
