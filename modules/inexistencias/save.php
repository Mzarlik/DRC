<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');
\Core\Auth::check();

// modules/inexistencias/save.php
header('Content-Type: application/json; charset=utf-8');

use Core\Services\GestorInexistencias;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validar CSRF
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido o faltante.']);
        exit;
    }

    // 2. Capturar entradas
    $linea_pago = $_POST['linea_pago'] ?? '';
    $nombre_completo = $_POST['nombre_completo'] ?? '';
    $fecha_tramite = $_POST['fecha_tramite'] ?? '';
    $fecha_llegada = $_POST['fecha_llegada'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $tipo_constancia = $_POST['tipo_constancia'] ?? 'INEXISTENCIA_NACIMIENTO';

    // 3. Delegar lógica de negocio al servicio
    $res = GestorInexistencias::registrarInexistencia($tipo_constancia, $linea_pago, $fecha_tramite, $fecha_llegada, $nombre_completo, $observaciones);
    echo json_encode($res);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
