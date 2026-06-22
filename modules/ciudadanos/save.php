<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/ciudadanos/save.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
    $apellido_paterno = mb_strtoupper(trim($_POST['apellido_paterno'] ?? ''), 'UTF-8');
    $apellido_materno = mb_strtoupper(trim($_POST['apellido_materno'] ?? ''), 'UTF-8');
    $curp = mb_strtoupper(trim($_POST['curp'] ?? ''), 'UTF-8');
    $sexo = trim($_POST['sexo'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');

    // CURP puede ser nulo en la BD
    if ($curp === '') {
        $curp = null;
    }

    try {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO ciudadanos (curp, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, estado_vital) 
                VALUES (:curp, :nombre, :apellido_paterno, :apellido_materno, :sexo, :fecha_nacimiento, 'VIVO')";
        
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            ':curp' => $curp,
            ':nombre' => $nombre,
            ':apellido_paterno' => $apellido_paterno,
            ':apellido_materno' => $apellido_materno,
            ':sexo' => $sexo,
            ':fecha_nacimiento' => $fecha_nacimiento
        ]);

        if ($result) {
            \Core\Audit::log('INSERT', 'ciudadanos', 'Se registró un nuevo trámite/registro.');
        echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro.']);
        }

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'La CURP ingresada ya se encuentra registrada en el sistema.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error de integridad en la base de datos.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
