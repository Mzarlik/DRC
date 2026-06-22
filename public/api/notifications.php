<?php
// public/api/notifications.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
    exit;
}

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    
    // 1. Consultar peticiones de aprobación si el usuario es coordinador (ADMIN o SUPERVISOR)
    $coordinatorNotifications = [];
    if (in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'SUPERVISOR'])) {
        $stmtCorr = $pdo->query("
            SELECT p.folio, p.fecha_creacion, c.nombre, c.apellido_paterno
            FROM peticiones p
            JOIN ciudadanos c ON p.ciudadano_id = c.id
            WHERE p.tipo_peticion = 'CORRECCION_ACTA' AND p.estatus = 'ABIERTA'
            ORDER BY p.fecha_creacion DESC
        ");
        while ($row = $stmtCorr->fetch(PDO::FETCH_ASSOC)) {
            $timeDiff = time() - strtotime($row['fecha_creacion']);
            if ($timeDiff < 60) {
                $timeStr = "Hace unos instantes";
            } elseif ($timeDiff < 3600) {
                $timeStr = "Hace " . round($timeDiff / 60) . " min";
            } elseif ($timeDiff < 86400) {
                $timeStr = "Hace " . round($timeDiff / 3600) . " hrs";
            } else {
                $timeStr = date('d/m/Y H:i', strtotime($row['fecha_creacion']));
            }
            
            $coordinatorNotifications[] = [
                'tipo' => 'correccion_aprobacion',
                'title' => 'Aprobación Requerida',
                'desc' => "Corrección de acta de " . htmlspecialchars($row['nombre'] . ' ' . $row['apellido_paterno']) . " requiere aprobación (Folio: " . htmlspecialchars($row['folio']) . ")",
                'time' => $timeStr,
                'icon' => 'fa-shield-halved',
                'color' => 'text-danger fw-bold'
            ];
        }
    }

    // 1.5. Consultar reportes asíncronos generados para el usuario actual que estén completados o fallidos
    $jobNotifications = [];
    $stmtJobs = $pdo->prepare("
        SELECT id, type, status, updated_at, file_path
        FROM jobs
        WHERE user_id = ? AND status IN ('completed', 'failed')
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmtJobs->execute([$_SESSION['user_id']]);
    while ($row = $stmtJobs->fetch(PDO::FETCH_ASSOC)) {
        $timeDiff = time() - strtotime($row['updated_at']);
        if ($timeDiff < 60) {
            $timeStr = "Hace unos instantes";
        } elseif ($timeDiff < 3600) {
            $timeStr = "Hace " . round($timeDiff / 60) . " min";
        } elseif ($timeDiff < 86400) {
            $timeStr = "Hace " . round($timeDiff / 3600) . " hrs";
        } else {
            $timeStr = date('d/m/Y H:i', strtotime($row['updated_at']));
        }
        
        $title = $row['status'] === 'completed' ? 'Reporte Listo' : 'Error en Reporte';
        $color = $row['status'] === 'completed' ? 'text-success fw-bold' : 'text-danger fw-bold';
        $icon = $row['status'] === 'completed' ? 'fa-file-excel' : 'fa-triangle-exclamation';
        
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $is_public = (strpos($referer, '/modules/') === false);
        $relative_link = $is_public ? 'exports/' . basename($row['file_path']) : '../../public/exports/' . basename($row['file_path']);
        
        if ($row['status'] === 'completed') {
            $desc = "El reporte " . ($row['type'] === 'export_inexistencias' ? 'Inexistencias' : 'General') . " ya está listo para <a href=\"" . $relative_link . "\" class=\"fw-bold text-decoration-none\" download>Descargar aquí</a>";
        } else {
            $desc = "Error al generar reporte " . ($row['type'] === 'export_inexistencias' ? 'Inexistencias' : 'General');
        }

        $jobNotifications[] = [
            'tipo' => 'job_notification',
            'title' => $title,
            'desc' => $desc,
            'time' => $timeStr,
            'icon' => $icon,
            'color' => $color
        ];
    }

    // 2. Fetch latest 5 activities across all modules
    $query = "
        SELECT 'ciudadano' AS tipo, CONCAT(nombre, ' ', apellido_paterno) AS ref, creado_en AS fecha FROM ciudadanos
        UNION ALL
        SELECT 'nacimiento' AS tipo, numero_acta AS ref, creado_en AS fecha FROM nacimientos
        UNION ALL
        SELECT 'defuncion' AS tipo, numero_acta AS ref, creado_en AS fecha FROM defunciones
        UNION ALL
        SELECT 'foranea' AS tipo, numero_acta AS ref, creado_en AS fecha FROM foraneas
        UNION ALL
        SELECT 'peticion' AS tipo, folio AS ref, fecha_creacion AS fecha FROM peticiones
        ORDER BY fecha DESC LIMIT 5
    ";
    
    $stmt = $pdo->query($query);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifications = [];
    foreach ($activities as $act) {
        $icon = 'fa-info-circle';
        $color = 'text-primary';
        $title = 'Registro';
        $desc = '';
        
        switch ($act['tipo']) {
            case 'ciudadano':
                $icon = 'fa-user-plus';
                $color = 'text-success';
                $title = 'Nuevo Ciudadano';
                $desc = "Se registró a " . htmlspecialchars($act['ref']);
                break;
            case 'nacimiento':
                $icon = 'fa-baby';
                $color = 'text-info';
                $title = 'Nuevo Nacimiento';
                $desc = "Acta de nacimiento registrada: N° " . htmlspecialchars($act['ref']);
                break;
            case 'defuncion':
                $icon = 'fa-book-skull';
                $color = 'text-dark';
                $title = 'Defunción Registrada';
                $desc = "Acta de defunción registrada: N° " . htmlspecialchars($act['ref']);
                break;
            case 'foranea':
                $icon = 'fa-plane-arrival';
                $color = 'text-warning';
                $title = 'Acta Foránea';
                $desc = "Acta foránea recibida: N° " . htmlspecialchars($act['ref']);
                break;
            case 'peticion':
                $icon = 'fa-ticket';
                $color = 'text-danger';
                $title = 'Nuevo Ticket';
                $desc = "Petición creada con Folio " . htmlspecialchars($act['ref']);
                break;
        }
        
        // Human readable time diff
        $timeDiff = time() - strtotime($act['fecha']);
        if ($timeDiff < 60) {
            $timeStr = "Hace unos instantes";
        } elseif ($timeDiff < 3600) {
            $timeStr = "Hace " . round($timeDiff / 60) . " min";
        } elseif ($timeDiff < 86400) {
            $timeStr = "Hace " . round($timeDiff / 3600) . " hrs";
        } else {
            $timeStr = date('d/m/Y H:i', strtotime($act['fecha']));
        }
        
        $notifications[] = [
            'tipo' => $act['tipo'],
            'title' => $title,
            'desc' => $desc,
            'time' => $timeStr,
            'icon' => $icon,
            'color' => $color
        ];
    }
    
    // 3. Combinar las alertas de aprobación, las de reportes asíncronos y las notificaciones habituales
    $notifications = array_merge($coordinatorNotifications, $jobNotifications, $notifications);
    
    echo json_encode([
        'status' => 'success',
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
