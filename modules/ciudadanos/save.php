<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/ciudadanos/save.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!\Core\Auth::validateCSRF($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
    $apellido_paterno = mb_strtoupper(trim($_POST['apellido_paterno'] ?? ''), 'UTF-8');
    $apellido_materno = mb_strtoupper(trim($_POST['apellido_materno'] ?? ''), 'UTF-8');
    $curp = mb_strtoupper(trim($_POST['curp'] ?? ''), 'UTF-8');
    $sexo = trim($_POST['sexo'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');

    // Procesamiento de CURP con Blind Index (HMAC) e IV aleatorio (AES-256)
    $curp_encrypted = null;
    $curp_bindex = null;
    if ($curp !== '') {
        $curp_bindex = \Core\Encryption::getBlindIndex($curp);
        $curp_encrypted = \Core\Encryption::encrypt($curp);
    }

    try {
        $pdo = Database::getConnection();

        $sql = "INSERT INTO ciudadanos (curp, curp_bindex, curp_encrypted, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, estado_vital) 
                VALUES (:curp, :curp_bindex, :curp_encrypted, :nombre, :apellido_paterno, :apellido_materno, :sexo, :fecha_nacimiento, 'VIVO')";
        
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            ':curp' => $curp_encrypted,
            ':curp_bindex' => $curp_bindex,
            ':curp_encrypted' => $curp_encrypted,
            ':nombre' => $nombre,
            ':apellido_paterno' => $apellido_paterno,
            ':apellido_materno' => $apellido_materno,
            ':sexo' => $sexo,
            ':fecha_nacimiento' => $fecha_nacimiento
        ]);

        if ($result) {
            \Core\Auditoria::logAccion('Ciudadanos', 'CREAR', "Se registró un nuevo ciudadano: $nombre $apellido_paterno $apellido_materno");
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
