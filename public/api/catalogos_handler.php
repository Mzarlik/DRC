<?php
// public/api/catalogos_handler.php
header('Content-Type: application/json; charset=utf-8');

require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::check();

if (!in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'SUPERVISOR'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

use Core\Catalogo;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'get_opciones') {
        $catalogo = $_GET['catalogo'] ?? '';
        if (empty($catalogo)) {
            throw new Exception("Catálogo no especificado.");
        }
        // Retornamos todas las opciones (incluso inactivas) para la interfaz de administración
        $opciones = Catalogo::getOpciones($catalogo, false);
        echo json_encode(['status' => 'success', 'data' => $opciones]);
        exit;
        
    } elseif ($action === 'agregar_opcion') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Método de petición no soportado.");
        }
        
        $catalogo = $_POST['catalogo'] ?? '';
        $clave = $_POST['clave'] ?? '';
        $valor = $_POST['valor'] ?? '';
        $orden = intval($_POST['orden'] ?? 0);
        
        if (empty($catalogo) || empty($clave) || empty($valor)) {
            throw new Exception("Todos los campos obligatorios deben completarse.");
        }
        
        // Limpiar y validar clave para evitar caracteres especiales problemáticos en base de datos
        $cleanClave = preg_replace('/[^A-Z0-9_]/', '', strtoupper(str_replace(' ', '_', trim($clave))));
        if (empty($cleanClave)) {
            throw new Exception("La clave ingresada es inválida.");
        }
        
        $res = Catalogo::agregarOpcion($catalogo, $cleanClave, trim($valor), $orden);
        
        if ($res) {
            \Core\Auditoria::logAccion('Catalogos', 'CREAR', "Se agregó la opción '$cleanClave' ($valor) al catálogo '$catalogo'.");
            echo json_encode(['status' => 'success', 'message' => 'Opcón agregada correctamente.']);
        } else {
            throw new Exception("No se pudo agregar la opción al catálogo.");
        }
        exit;
        
    } elseif ($action === 'toggle_estado') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Método de petición no soportado.");
        }
        
        $id = intval($_POST['id'] ?? 0);
        $activo = intval($_POST['activo'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception("ID de opción inválido.");
        }
        
        $res = Catalogo::toggleEstadoOpcion($id, $activo);
        
        if ($res) {
            \Core\Auditoria::logAccion('Catalogos', 'EDITAR', "Se cambió el estado de la opción de catálogo ID $id a " . ($activo ? 'ACTIVO' : 'INACTIVO'));
            echo json_encode(['status' => 'success', 'message' => 'Estado actualizado correctamente.']);
        } else {
            throw new Exception("No se pudo actualizar el estado de la opción.");
        }
        exit;
        
    } else {
        throw new Exception("Acción no soportada.");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
