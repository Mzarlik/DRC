<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// public/api/stats.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    $today = date('Y-m-d');

    // 1. Contadores Globales de Tarjetas Superiores
    // Trámites de hoy (Nacimientos, Defunciones, Inexistencias)
    $stmtHoy = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM nacimientos WHERE fecha_registro = '$today') +
        (SELECT COUNT(*) FROM defunciones WHERE fecha_registro = '$today') +
        (SELECT COUNT(*) FROM inexistencias WHERE DATE(creado_en) = '$today') AS total_hoy");
    $totalHoy = $stmtHoy->fetchColumn();

    // Peticiones pendientes
    $stmtPendientes = $pdo->query("SELECT COUNT(*) FROM peticiones WHERE estatus IN ('ABIERTA', 'EN_PROGRESO')");
    $peticionesPendientes = $stmtPendientes->fetchColumn();

    // Inexistencias pendientes
    $stmtInxPendientes = $pdo->query("SELECT COUNT(*) FROM inexistencias WHERE estatus = 'PENDIENTE'");
    $inexistenciasPendientes = $stmtInxPendientes->fetchColumn();

    // Foráneas validadas (Ejemplo de finalizados)
    $stmtValidadas = $pdo->query("SELECT COUNT(*) FROM foraneas WHERE estatus = 'VALIDADA'");
    $foraneasValidadas = $stmtValidadas->fetchColumn();

    // 2. Datos para Gráfica de Distribución (Nacimientos vs Defunciones vs Inexistencias)
    $stmtDist = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM nacimientos) AS nacimientos,
        (SELECT COUNT(*) FROM defunciones) AS defunciones,
        (SELECT COUNT(*) FROM inexistencias) AS inexistencias,
        (SELECT COUNT(*) FROM foraneas) AS foraneas
    ");
    $distribucion = $stmtDist->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'cards' => [
            'tramites_hoy' => (int)$totalHoy,
            'peticiones_pendientes' => (int)$peticionesPendientes,
            'inexistencias_pendientes' => (int)$inexistenciasPendientes,
            'foraneas_validadas' => (int)$foraneasValidadas
        ],
        'chart_data' => [
            'labels' => ['Nacimientos', 'Defunciones', 'Inexistencias', 'Foráneas'],
            'data' => [
                (int)$distribucion['nacimientos'],
                (int)$distribucion['defunciones'],
                (int)$distribucion['inexistencias'],
                (int)$distribucion['foraneas']
            ]
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener estadísticas.']);
}
