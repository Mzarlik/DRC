<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_nacimientos');
\Core\Auth::check();

// modules/nacimientos/save.php
header('Content-Type: application/json; charset=utf-8');

use Core\Services\GestorNacimientos;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $numero_acta = $_POST['numero_acta'] ?? '';
    $fecha_registro = $_POST['fecha_registro'] ?? '';
    $lugar_nacimiento = $_POST['lugar_nacimiento'] ?? '';
    
    $ciudadano_id = !empty($_POST['ciudadano_id']) ? intval($_POST['ciudadano_id']) : null;
    $padre_id = !empty($_POST['padre_id']) ? intval($_POST['padre_id']) : null;
    $madre_id = !empty($_POST['madre_id']) ? intval($_POST['madre_id']) : null;

    $res = GestorNacimientos::registrarNacimiento($numero_acta, $ciudadano_id, $padre_id, $madre_id, $lugar_nacimiento, $fecha_registro);
    echo json_encode($res);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
