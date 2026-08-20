<?php
// public/api/download_export.php
// Descarga segura de exportaciones generadas por el worker (solo propietario o ADMIN).
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::check();

require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

$fileName = basename($_GET['file'] ?? '');
if ($fileName === '' || $fileName === '.' || $fileName === '..') {
    http_response_code(400);
    die('Error: Archivo no especificado.');
}

// Asegurar que solo se descarguen archivos de exportación permitidos (.xlsx, .csv, .pdf)
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'csv', 'pdf', 'zip'], true)) {
    http_response_code(400);
    die('Error: Tipo de archivo no permitido.');
}

try {
    $pdo = Database::getReadConnection();
    
    // Buscar en la tabla jobs
    $stmt = $pdo->prepare("SELECT id, user_id, type, status, file_path FROM jobs WHERE file_path LIKE ? AND status = 'completed' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['%' . $fileName]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no está en jobs, intentar buscar en export_jobs como fallback
    if (!$job) {
        try {
            $stmtExp = $pdo->prepare("SELECT id, usuario_id AS user_id, modulo AS type, estado AS status, archivo_ruta AS file_path FROM export_jobs WHERE (archivo_nombre = ? OR archivo_ruta LIKE ?) AND estado = 'COMPLETADO' ORDER BY id DESC LIMIT 1");
            $stmtExp->execute([$fileName, '%' . $fileName]);
            $job = $stmtExp->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignorar si la tabla export_jobs no existe
        }
    }

    if (!$job) {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Archivo no disponible - ERP DRC</title>
            <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
            <div class="card p-4 shadow-sm border-0 text-center" style="max-width: 460px;">
                <div class="mb-3 text-warning"><i class="fa-solid fa-triangle-exclamation fa-3x"></i></div>
                <h4 class="fw-bold">Reporte en Proceso o no Encontrado</h4>
                <p class="text-muted small">El archivo solicitado aún se está procesando en segundo plano o ha expirado. Por favor, revise el centro de notificaciones.</p>
                <a href="javascript:history.back()" class="btn btn-primary">Volver al ERP</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    $isOwner = (int)$job['user_id'] === (int)($_SESSION['user_id'] ?? 0);
    $isAdmin = in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'SUPERVISOR', 'COORDINADOR'], true) || \Core\Auth::canExportar();
    if (!$isOwner && !$isAdmin) {
        http_response_code(403);
        die('Error: No tiene autorización para descargar este archivo.');
    }

    $fullPath = dirname(__DIR__) . '/exports/' . $fileName;
    if (!is_file($fullPath)) {
        http_response_code(404);
        die('Error: El archivo ya no se encuentra en el servidor (expiró tras 48 hrs).');
    }

    // MIME Types
    $contentTypes = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv; charset=utf-8',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip'
    ];
    $contentType = $contentTypes[$ext] ?? 'application/octet-stream';

    // Limpiar buffers de salida previos
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    header('X-Content-Type-Options: nosniff');
    
    readfile($fullPath);
    exit;

} catch (\Throwable $e) {
    http_response_code(500);
    die('Error al procesar la descarga del archivo.');
}