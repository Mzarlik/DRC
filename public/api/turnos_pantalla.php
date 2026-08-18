<?php
// public/api/turnos_pantalla.php
// Pantalla pública de turnos (solo lectura, sin sesión). Devuelve el folio en
// atención, los próximos turnos y el conteo de espera.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();

    $atendiendo = $pdo->query("SELECT folio, modulo_atencion, ventanilla FROM turnos WHERE estado = 'ATENDIENDO' ORDER BY atendido_en DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT folio, modulo_atencion FROM turnos WHERE estado = 'EN_ESPERA' ORDER BY creado_en ASC LIMIT 8");
    $proximos = $stmt->fetchAll();

    $enEspera = (int)$pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'EN_ESPERA'")->fetchColumn();

    $ultimos = $pdo->query("SELECT folio, modulo_atencion FROM turnos WHERE estado = 'COMPLETADO' ORDER BY finalizado_en DESC LIMIT 5")->fetchAll();

    echo json_encode([
        'status' => 'success',
        'atendiendo' => $atendiendo,
        'proximos' => $proximos,
        'en_espera' => $enEspera,
        'ultimos' => $ultimos
    ]);
} catch (\Throwable $e) {
    error_log('api/turnos_pantalla: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener el estado de los turnos.', 'atendiendo' => null, 'proximos' => [], 'en_espera' => 0, 'ultimos' => []]);
}
