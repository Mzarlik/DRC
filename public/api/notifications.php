<?php
// public/api/notifications.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Auth.php';

\Core\Auth::initSession();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.', 'notifications' => []]);
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();
    $userId = (int)$_SESSION['user_id'];
    $userRol = $_SESSION['user_rol'] ?? 'OPERADOR';
    $notifications = [];

    // Función auxiliar para formatear tiempo transcurrido en español
    $formatearTiempo = function($fechaStr) {
        if (!$fechaStr) return 'Reciente';
        $timestamp = strtotime($fechaStr);
        $diff = time() - $timestamp;
        if ($diff < 60) return "Hace un momento";
        if ($diff < 3600) return "Hace " . max(1, round($diff / 60)) . " min";
        if ($diff < 86400) return "Hace " . round($diff / 3600) . " hrs";
        if ($diff < 172800) return "Ayer " . date('H:i', $timestamp);
        return date('d/m/Y H:i', $timestamp);
    };

    // 1. APROBACIONES Y EXPEDIENTES EN SEGUIMIENTO (Para Coordinadores, Supervisores y Admin)
    if (in_array($userRol, ['ADMIN', 'SUPERVISOR', 'COORDINADOR'])) {
        $stmtSeg = $pdo->query("
            SELECT p.id, p.folio, p.tipo_peticion, p.fecha_creacion, p.estatus,
                   c.nombre, c.apellido_paterno
            FROM peticiones p
            LEFT JOIN ciudadanos c ON p.ciudadano_id = c.id
            WHERE p.estatus IN ('ABIERTA', 'EN_PROGRESO')
            ORDER BY p.id DESC
            LIMIT 4
        ");
        while ($row = $stmtSeg->fetch(PDO::FETCH_ASSOC)) {
            $solicitante = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido_paterno'] ?? ''));
            if (!$solicitante) $solicitante = 'Ciudadano';

            $tipoTitulo = 'Expediente en Trámite';
            $icon = 'fa-folder-open';
            $color = 'text-primary';

            if ($row['tipo_peticion'] === 'CORRECCION_ACTA') {
                $tipoTitulo = 'Dictamen / Aprobación';
                $icon = 'fa-shield-halved';
                $color = 'text-danger';
            } elseif ($row['tipo_peticion'] === 'DIGITALIZACION') {
                $tipoTitulo = 'Validación Base Nacional';
                $icon = 'fa-cloud-arrow-up';
                $color = 'text-info';
            } elseif ($row['tipo_peticion'] === 'ACLARACION') {
                $tipoTitulo = 'Aclaración de Libro';
                $icon = 'fa-book';
                $color = 'text-warning';
            }

            $notifications[] = [
                'id' => 'seg-' . $row['id'],
                'tipo' => 'seguimiento',
                'title' => $tipoTitulo,
                'desc' => "Folio " . htmlspecialchars($row['folio']) . " — " . htmlspecialchars($solicitante) . " (" . $row['estatus'] . ")",
                'time' => $formatearTiempo($row['fecha_creacion']),
                'icon' => $icon,
                'color' => $color,
                'url' => '/DRC/modules/peticiones/index.php',
                'is_urgent' => ($row['tipo_peticion'] === 'CORRECCION_ACTA')
            ];
        }
    }

    // 2. REPORTES Y EXPORTACIONES ASÍNCRONAS DEL USUARIO (jobs y export_jobs)
    try {
        $stmtJobs = $pdo->prepare("
            SELECT id, type, status, file_path, error_message, created_at, updated_at
            FROM jobs
            WHERE user_id = ? AND status IN ('completed', 'failed') AND type LIKE 'export_%'
            ORDER BY id DESC
            LIMIT 5
        ");
        $stmtJobs->execute([$userId]);
        
        $moduleNames = [
            'export_general_report' => 'Reportes Cruzados',
            'export_inexistencias'  => 'Inexistencias',
            'export_ciudadanos'     => 'Ciudadanos',
            'export_nacimientos'    => 'Nacimientos',
            'export_matrimonios'    => 'Matrimonios',
            'export_divorcios'      => 'Divorcios',
            'export_defunciones'    => 'Defunciones',
            'export_inscripciones'  => 'Inscripciones',
            'export_reconocimientos'=> 'Reconocimientos',
            'export_actas_locales'  => 'Actas Locales',
            'export_foraneas'       => 'Actas Foráneas',
            'export_usuarios'       => 'Usuarios',
            'export_auditoria'      => 'Auditoría',
            'export_errores'        => 'Errores'
        ];

        while ($j = $stmtJobs->fetch(PDO::FETCH_ASSOC)) {
            $isOk = ($j['status'] === 'completed');
            $nomArchivo = basename($j['file_path'] ?? '');
            $moduloLabel = $moduleNames[$j['type']] ?? ucfirst(str_replace(['export_', '_'], ['', ' '], $j['type']));

            $notifications[] = [
                'id' => 'job-' . $j['id'],
                'tipo' => 'exportacion',
                'title' => $isOk ? 'Reporte Excel Listo' : 'Error en Reporte Excel',
                'desc' => $isOk 
                    ? "El reporte de " . htmlspecialchars($moduloLabel) . " está listo para descargar." 
                    : "No se pudo generar el reporte de " . htmlspecialchars($moduloLabel) . ".",
                'time' => $formatearTiempo($j['updated_at'] ?: $j['created_at']),
                'icon' => $isOk ? 'fa-file-excel' : 'fa-triangle-exclamation',
                'color' => $isOk ? 'text-success' : 'text-danger',
                'url' => ($isOk && $nomArchivo) ? '/DRC/public/api/download_export.php?file=' . urlencode($nomArchivo) : '#',
                'is_download' => $isOk
            ];
        }
    } catch (\Throwable $e) {
        // Silencioso si la tabla no está disponible
    }

    // 3. ACTAS FORÁNEAS PENDIENTES DE VALIDACIÓN
    try {
        $stmtFor = $pdo->query("
            SELECT id, numero_acta, estado_origen, creado_en 
            FROM foraneas 
            WHERE estatus = 'PENDIENTE' 
            ORDER BY id DESC 
            LIMIT 2
        ");
        while ($f = $stmtFor->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'id' => 'for-' . $f['id'],
                'tipo' => 'foranea',
                'title' => 'Acta Foránea por Validar',
                'desc' => "Acta N° " . htmlspecialchars($f['numero_acta']) . " (" . htmlspecialchars($f['estado_origen']) . ") pendiente de cotejo.",
                'time' => $formatearTiempo($f['creado_en']),
                'icon' => 'fa-plane-arrival',
                'color' => 'text-warning',
                'url' => '/DRC/modules/foraneas/index.php'
            ];
        }
    } catch (\Throwable $e) {}

    // 4. INEXISTENCIAS PENDIENTES
    try {
        $stmtInex = $pdo->query("
            SELECT id, linea_pago, tipo_constancia, creado_en 
            FROM inexistencias 
            WHERE estatus = 'PENDIENTE' 
            ORDER BY id DESC 
            LIMIT 2
        ");
        while ($inx = $stmtInex->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'id' => 'inx-' . $inx['id'],
                'tipo' => 'inexistencia',
                'title' => 'Inexistencia Pendiente',
                'desc' => "Constancia de " . htmlspecialchars($inx['tipo_constancia'] ?? 'Inexistencia') . " (Línea " . htmlspecialchars($inx['linea_pago']) . ").",
                'time' => $formatearTiempo($inx['creado_en']),
                'icon' => 'fa-file-circle-question',
                'color' => 'text-info',
                'url' => '/DRC/modules/inexistencias/index.php'
            ];
        }
    } catch (\Throwable $e) {}

    // 5. PETICIONES RÁPIDAS DE VENTANILLA DEL DÍA
    try {
        $stmtVen = $pdo->query("
            SELECT COUNT(*) FROM peticiones_ventanilla 
            WHERE DATE(creado_en) = CURRENT_DATE() AND estatus = 'PENDIENTE'
        ");
        $pendientesHoy = (int)$stmtVen->fetchColumn();
        if ($pendientesHoy > 0) {
            $notifications[] = [
                'id' => 'ven-hoy',
                'tipo' => 'peticion_rapida',
                'title' => 'Ventanilla Rápida Activa',
                'desc' => "Hay {$pendientesHoy} trámite(s) de mostrador pendientes de entrega hoy.",
                'time' => 'Hoy',
                'icon' => 'fa-bolt',
                'color' => 'text-warning',
                'url' => '/DRC/modules/peticion_rapida/index.php'
            ];
        }
    } catch (\Throwable $e) {}

    // 6. ACTIVIDAD RECIENTE EN EL REGISTRO CIVIL (Últimos registros generales)
    $stmtAct = $pdo->query("
        (SELECT 'ciudadano' AS tipo, CONCAT(nombre, ' ', apellido_paterno) AS ref, creado_en AS fecha, '/DRC/modules/ciudadanos/index.php' AS url FROM ciudadanos ORDER BY id DESC LIMIT 2)
        UNION ALL
        (SELECT 'nacimiento' AS tipo, CONCAT('Acta N° ', numero_acta) AS ref, creado_en AS fecha, '/DRC/modules/nacimientos/index.php' AS url FROM nacimientos ORDER BY id DESC LIMIT 2)
        ORDER BY fecha DESC LIMIT 3
    ");
    while ($act = $stmtAct->fetch(PDO::FETCH_ASSOC)) {
        $notifications[] = [
            'id' => 'act-' . md5($act['tipo'] . $act['ref'] . $act['fecha']),
            'tipo' => $act['tipo'],
            'title' => ($act['tipo'] === 'ciudadano') ? 'Nuevo Ciudadano en Padrón' : 'Nuevo Registro de Nacimiento',
            'desc' => htmlspecialchars($act['ref']),
            'time' => $formatearTiempo($act['fecha']),
            'icon' => ($act['tipo'] === 'ciudadano') ? 'fa-user-plus' : 'fa-baby',
            'color' => 'text-success',
            'url' => $act['url']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'notifications' => $notifications,
        'total' => count($notifications)
    ]);

} catch (\Throwable $e) {
    error_log("public/api/notifications: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al cargar notificaciones.',
        'notifications' => [],
        'total' => 0
    ]);
}
