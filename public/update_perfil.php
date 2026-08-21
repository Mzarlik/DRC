<?php
// public/update_perfil.php
header('Content-Type: application/json; charset=utf-8');
require_once '../core/Auth.php';

// Verify session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
    exit;
}

require_once '../core/Database.php';
use Core\Database;

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!\Core\Auth::validateCSRF($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido. Recargue la página e intente de nuevo.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        if ($action === 'update_info') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');

            if (empty($nombre) || empty($correo)) {
                echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
                exit;
            }

            // Check if email already exists for another user
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo AND id != :id");
            $stmtCheck->execute([':correo' => $correo, ':id' => $_SESSION['user_id']]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya está registrado por otro usuario.']);
                exit;
            }

            // Update user info
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo WHERE id = :id");
            $stmtUpdate->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':id' => $_SESSION['user_id']
            ]);

            // Update session variables
            $_SESSION['user_nombre'] = $nombre;

            \Core\Auditoria::logAccion('Perfil', 'EDITAR', "El usuario actualizó su información de perfil (Nombre: $nombre, Correo: $correo)");

            echo json_encode(['status' => 'success']);
            exit;
        } 
        
        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña y su confirmación no coinciden.']);
                exit;
            }

            if (strlen($newPassword) < 8) {
                echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
                exit;
            }

            if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
                echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña debe incluir al menos una mayúscula y un número.']);
                exit;
            }

            // Fetch stored password hash
            $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.']);
                exit;
            }

            // Update password hash
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET password_hash = :hash WHERE id = :id");
            $stmtUpdate->execute([
                ':hash' => $newHash,
                ':id' => $_SESSION['user_id']
            ]);

            session_regenerate_id(true); // Regenerar ID al cambiar credenciales de seguridad

            \Core\Auditoria::logAccion('Perfil', 'EDITAR', 'El usuario actualizó su contraseña de seguridad');

            echo json_encode(['status' => 'success']);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Acción no permitida.']);

    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos. Intente de nuevo más tarde.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no soportado.']);
}
