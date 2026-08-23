<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::check();

// public/api/stats.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();
    $today = date('Y-m-d');

    // 1. Contadores Globales de Tarjetas Superiores
    // Trámites de hoy (Suma de todos los 9 módulos procesados hoy)
    $totalHoy = 0;
    $countStmts = [
        "SELECT COUNT(*) FROM nacimientos WHERE fecha_registro = ?",
        "SELECT COUNT(*) FROM defunciones WHERE fecha_registro = ?",
        "SELECT COUNT(*) FROM matrimonios WHERE fecha_registro = ?",
        "SELECT COUNT(*) FROM divorcios WHERE fecha_registro = ?",
        "SELECT COUNT(*) FROM reconocimientos WHERE fecha_registro = ?",
        "SELECT COUNT(*) FROM inscripciones WHERE fecha_registro = ?",
        "SELECT COUNT(*) FROM inexistencias WHERE fecha_tramite = ?",
        "SELECT COUNT(*) FROM foraneas WHERE fecha_recepcion = ?",
        "SELECT COUNT(*) FROM tramites_curp WHERE fecha_registro = ?"
    ];
    foreach ($countStmts as $sql) {
        $stmtCount = $pdo->prepare($sql);
        $stmtCount->bindValue(1, $today, PDO::PARAM_STR);
        $stmtCount->execute();
        $totalHoy += (int)$stmtCount->fetchColumn();
    }

    // Peticiones pendientes
    $stmtPendientes = $pdo->query("SELECT COUNT(*) FROM peticiones WHERE estatus IN ('ABIERTA', 'EN_PROGRESO')");
    $peticionesPendientes = $stmtPendientes->fetchColumn();

    // Inexistencias pendientes
    $stmtInxPendientes = $pdo->query("SELECT COUNT(*) FROM inexistencias WHERE estatus = 'PENDIENTE'");
    $inexistenciasPendientes = $stmtInxPendientes->fetchColumn();

    // Foráneas validadas
    $stmtValidadas = $pdo->query("SELECT COUNT(*) FROM foraneas WHERE estatus = 'VALIDADA'");
    $foraneasValidadas = $stmtValidadas->fetchColumn();

    // 2. Definición de Costos Proyectados y Carga Operativa
    $costs = [
        'Nacimientos' => 120,
        'Defunciones' => 180,
        'Matrimonios' => 420,
        'Divorcios' => 650,
        'Reconocimientos' => 190,
        'Inscripciones' => 580,
        'Inexistencias' => 220,
        'Foráneas' => 290,
        'Trámites CURP' => 80
    ];

    $counts = [
        'Nacimientos' => (int)$pdo->query("SELECT COUNT(*) FROM nacimientos")->fetchColumn(),
        'Defunciones' => (int)$pdo->query("SELECT COUNT(*) FROM defunciones")->fetchColumn(),
        'Matrimonios' => (int)$pdo->query("SELECT COUNT(*) FROM matrimonios")->fetchColumn(),
        'Divorcios' => (int)$pdo->query("SELECT COUNT(*) FROM divorcios")->fetchColumn(),
        'Reconocimientos' => (int)$pdo->query("SELECT COUNT(*) FROM reconocimientos")->fetchColumn(),
        'Inscripciones' => (int)$pdo->query("SELECT COUNT(*) FROM inscripciones")->fetchColumn(),
        'Inexistencias' => (int)$pdo->query("SELECT COUNT(*) FROM inexistencias")->fetchColumn(),
        'Foráneas' => (int)$pdo->query("SELECT COUNT(*) FROM foraneas")->fetchColumn(),
        'Trámites CURP' => (int)$pdo->query("SELECT COUNT(*) FROM tramites_curp")->fetchColumn(),
    ];

    // Calcular recaudación por módulo y total
    $recaudacion_data = [];
    $recaudacion_total = 0;
    foreach ($counts as $module => $count) {
        $amount = $count * $costs[$module];
        $recaudacion_data[] = $amount;
        $recaudacion_total += $amount;
    }

    // 3. Trámites Procesados por Día (Últimos 7 días) unificados eficientemente
    $six_days_ago = date('Y-m-d', strtotime('-6 days'));
    $dates_range = [];
    $daily_counts = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dates_range[] = $d;
        $daily_counts[$d] = 0;
    }

    $queries = [
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM nacimientos WHERE fecha_registro >= ? GROUP BY fecha_registro",
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM defunciones WHERE fecha_registro >= ? GROUP BY fecha_registro",
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM matrimonios WHERE fecha_registro >= ? GROUP BY fecha_registro",
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM divorcios WHERE fecha_registro >= ? GROUP BY fecha_registro",
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM reconocimientos WHERE fecha_registro >= ? GROUP BY fecha_registro",
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM inscripciones WHERE fecha_registro >= ? GROUP BY fecha_registro",
        "SELECT fecha_tramite AS fecha, COUNT(*) AS cnt FROM inexistencias WHERE fecha_tramite >= ? GROUP BY fecha_tramite",
        "SELECT fecha_recepcion AS fecha, COUNT(*) AS cnt FROM foraneas WHERE fecha_recepcion >= ? GROUP BY fecha_recepcion",
        "SELECT fecha_registro AS fecha, COUNT(*) AS cnt FROM tramites_curp WHERE fecha_registro >= ? GROUP BY fecha_registro"
    ];

    foreach ($queries as $q) {
        $stmt = $pdo->prepare($q);
        $stmt->bindValue(1, $six_days_ago, PDO::PARAM_STR);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fecha = $row['fecha'];
            if (isset($daily_counts[$fecha])) {
                $daily_counts[$fecha] += (int)$row['cnt'];
            }
        }
    }

    $labels_days = [];
    $data_days = [];
    $meses = ['Jan'=>'Ene', 'Feb'=>'Feb', 'Mar'=>'Mar', 'Apr'=>'Abr', 'May'=>'May', 'Jun'=>'Jun', 'Jul'=>'Jul', 'Aug'=>'Ago', 'Sep'=>'Sep', 'Oct'=>'Oct', 'Nov'=>'Nov', 'Dec'=>'Dic'];
    foreach ($dates_range as $d) {
        $dateObj = strtotime($d);
        $dayNum = date('d', $dateObj);
        $monthName = $meses[date('M', $dateObj)] ?? date('M', $dateObj);
        $labels_days[] = "$dayNum $monthName";
        $data_days[] = $daily_counts[$d];
    }

    echo json_encode([
        'status' => 'success',
        'cards' => [
            'tramites_hoy' => (int)$totalHoy,
            'peticiones_pendientes' => (int)$peticionesPendientes,
            'inexistencias_pendientes' => (int)$inexistenciasPendientes,
            'foraneas_validadas' => (int)$foraneasValidadas,
            'recaudacion_total' => (float)$recaudacion_total
        ],
        'processed_by_day' => [
            'labels' => $labels_days,
            'data' => $data_days
        ],
        'recaudacion_proyectada' => [
            'labels' => array_keys($counts),
            'data' => $recaudacion_data
        ],
        'carga_operativa' => [
            'labels' => array_keys($counts),
            'data' => array_values($counts)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener estadísticas. Intente de nuevo más tarde.']);
}

