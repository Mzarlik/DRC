<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_turnos');
\Core\Auth::check();

// modules/turnos/data.php (tablero: estadísticas + cola de turnos)
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();

    $enEspera = (int)$pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'EN_ESPERA'")->fetchColumn();

    $atendiendo = $pdo->query("SELECT folio, modulo_atencion FROM turnos WHERE estado = 'ATENDIENDO' ORDER BY atendido_en DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, folio, modulo_atencion, ciudadano_nombre, estado, ventanilla, DATE_FORMAT(creado_en, '%d/%m/%Y %H:%i') AS creado_en
                         FROM turnos
                         WHERE estado IN ('EN_ESPERA', 'ATENDIENDO')
                         ORDER BY FIELD(estado, 'ATENDIENDO', 'EN_ESPERA'), creado_en ASC
                         LIMIT 100");
    $turnos = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'en_espera' => $enEspera,
        'atendiendo_folio' => $atendiendo['folio'] ?? null,
        'atendiendo_modulo' => $atendiendo['modulo_atencion'] ?? null,
        'turnos' => $turnos
    ]);
} catch (\Throwable $e) {
    error_log('turnos/data: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => \Core\Services\ErrorMessages::humanize($e), 'en_espera' => 0, 'atendiendo_folio' => null, 'atendiendo_modulo' => null, 'turnos' => []]);
}
