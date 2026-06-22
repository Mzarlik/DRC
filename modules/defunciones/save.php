<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_defunciones');
\Core\Auth::check();

// modules/defunciones/save.php
header('Content-Type: application/json; charset=utf-8');

use Core\Services\GestorDefunciones;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $numero_acta = $_POST['numero_acta'] ?? '';
    $ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
    $fecha_defuncion = $_POST['fecha_defuncion'] ?? '';
    $fecha_registro = $_POST['fecha_registro'] ?? '';
    $causa_muerte = $_POST['causa_muerte'] ?? '';

    $res = GestorDefunciones::registrarDefuncion($numero_acta, $ciudadano_id, $fecha_defuncion, $fecha_registro, $causa_muerte);
    echo json_encode($res);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
