<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');
\Core\Auth::check();

// modules/inexistencias/update_status.php
header('Content-Type: application/json; charset=utf-8');

use Core\Services\GestorInexistencias;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!\Core\Auth::validateCSRF($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido o faltante.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$accion = strtoupper(trim($_POST['accion'] ?? ''));
$motivo = trim($_POST['motivo'] ?? '');

$acciones = [
    'FINALIZAR' => 'FINALIZADO',
    'CANCELAR'  => 'CANCELADO',
    'REACTIVAR' => 'PENDIENTE'
];

if (!isset($acciones[$accion])) {
    echo json_encode(['status' => 'error', 'message' => 'Acción inválida.']);
    exit;
}

echo json_encode(GestorInexistencias::actualizarEstatus($id, $acciones[$accion], $motivo));
