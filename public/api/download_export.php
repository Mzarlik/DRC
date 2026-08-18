<?php
// public/api/download_export.php
// Descarga segura de exportaciones generadas por el worker (solo propietario o ADMIN).
require_once '../../core/Auth.php';
\Core\Auth::check();

require_once '../../core/Database.php';
use Core\Database;

$fileName = basename($_GET['file'] ?? '');
if ($fileName === '' || $fileName === '.' || $fileName === '..') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Archivo no especificado.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, user_id, type, status, file_path FROM jobs WHERE file_path = ? AND status = 'completed' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['public/exports/' . $fileName]);
    $job = $stmt->fetch();

    if (!$job) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'El archivo no existe o aún no está listo.']);
        exit;
    }

    $isOwner = (int)$job['user_id'] === (int)($_SESSION['user_id'] ?? 0);
    $isAdmin = ($_SESSION['user_rol'] ?? '') === 'ADMIN';
    if (!$isOwner && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No autorizado para descargar este archivo.']);
        exit;
    }

    $fullPath = dirname(__DIR__) . '/exports/' . $fileName;
    if (!is_file($fullPath)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'El archivo ya no se encuentra en el servidor.']);
        exit;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('X-Content-Type-Options: nosniff');
    readfile($fullPath);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la descarga. Intente de nuevo más tarde.']);
    exit;
}