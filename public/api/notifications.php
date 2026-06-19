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
    
    // Fetch latest 5 activities across all modules
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
