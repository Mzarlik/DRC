<?php
// public/auth.php
header('Content-Type: application/json; charset=utf-8');
require_once '../core/Database.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($correo) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, ingrese sus credenciales.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->execute([':correo' => $correo]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['estatus'] == 0) {
                echo json_encode(['status' => 'error', 'message' => 'Su cuenta está inactiva.']);
                exit;
            }

            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];
            
            // Cargar permisos granulares
            $_SESSION['permiso_registro_nacimientos'] = (int)$user['permiso_registro_nacimientos'];
            $_SESSION['permiso_registro_matrimonios'] = (int)$user['permiso_registro_matrimonios'];
            $_SESSION['permiso_registro_divorcios'] = (int)$user['permiso_registro_divorcios'];
            $_SESSION['permiso_registro_defunciones'] = (int)$user['permiso_registro_defunciones'];
            $_SESSION['permiso_registro_inscripciones'] = (int)$user['permiso_registro_inscripciones'];
            $_SESSION['permiso_registro_reconocimientos'] = (int)$user['permiso_registro_reconocimientos'];
            $_SESSION['permiso_actas_locales'] = (int)$user['permiso_actas_locales'];
            $_SESSION['permiso_actas_foraneas'] = (int)$user['permiso_actas_foraneas'];
            $_SESSION['permiso_constancias'] = (int)$user['permiso_constancias'];
            $_SESSION['permiso_curp'] = (int)$user['permiso_curp'];
            $_SESSION['permiso_tickets'] = (int)$user['permiso_tickets'];

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
