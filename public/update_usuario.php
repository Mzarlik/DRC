<?php
// public/update_usuario.php
header('Content-Type: application/json; charset=utf-8');
require_once '../core/Auth.php';

// Verify session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requiere rol ADMIN.']);
    exit;
}

require_once '../core/Database.php';
use Core\Database;

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConnection();

        // 1. Create a new user
        if ($action === 'create') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $rol = trim($_POST['rol'] ?? 'OPERADOR');

            if (empty($nombre) || empty($correo) || empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
                exit;
            }

            // Check if email already exists
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo");
            $stmtCheck->execute([':correo' => $correo]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya está registrado.']);
                exit;
            }

            // Create user
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            // Default permissions based on role (admins get all-access, others get standard operators)
            $defaultVal = ($rol === 'ADMIN') ? 1 : 0; // Operators will start with no permissions by default until assigned

            $sql = "INSERT INTO usuarios (
                nombre, correo, password_hash, rol, estatus,
                permiso_registro_nacimientos, permiso_registro_matrimonios, permiso_registro_divorcios,
                permiso_registro_defunciones, permiso_registro_inscripciones, permiso_registro_reconocimientos,
                permiso_actas_locales, permiso_actas_foraneas, permiso_constancias, permiso_curp, permiso_tickets
            ) VALUES (
                :nombre, :correo, :hash, :rol, 1,
                :val, :val, :val,
                :val, :val, :val,
                :val, :val, :val, :val, :val
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':hash' => $passwordHash,
                ':rol' => $rol,
                ':val' => $defaultVal
            ]);

            \Core\Auditoria::logAccion('Administración', 'CREAR', "Se registró un nuevo usuario: $correo (Rol: $rol)");

            echo json_encode(['status' => 'success']);
            exit;
        }

        // 2. Update permissions and user status
        if ($action === 'update_perms') {
            $id = intval($_POST['id'] ?? 0);
            $rol = trim($_POST['rol'] ?? 'OPERADOR');
            $estatus = intval($_POST['estatus'] ?? 1);

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de usuario inválido.']);
                exit;
            }

            // Get permissions from checkboxes
            $p_nacimientos = isset($_POST['permiso_registro_nacimientos']) ? 1 : 0;
            $p_matrimonios = isset($_POST['permiso_registro_matrimonios']) ? 1 : 0;
            $p_divorcios = isset($_POST['permiso_registro_divorcios']) ? 1 : 0;
            $p_defunciones = isset($_POST['permiso_registro_defunciones']) ? 1 : 0;
            $p_inscripciones = isset($_POST['permiso_registro_inscripciones']) ? 1 : 0;
            $p_reconocimientos = isset($_POST['permiso_registro_reconocimientos']) ? 1 : 0;
            $p_actas_locales = isset($_POST['permiso_actas_locales']) ? 1 : 0;
            $p_actas_foraneas = isset($_POST['permiso_actas_foraneas']) ? 1 : 0;
            $p_constancias = isset($_POST['permiso_constancias']) ? 1 : 0;
            $p_curp = isset($_POST['permiso_curp']) ? 1 : 0;
            $p_tickets = isset($_POST['permiso_tickets']) ? 1 : 0;

            // If updating to ADMIN, force all permissions to 1
            if ($rol === 'ADMIN') {
                $p_nacimientos = $p_matrimonios = $p_divorcios = $p_defunciones = $p_inscripciones = $p_reconocimientos = $p_actas_locales = $p_actas_foraneas = $p_constancias = $p_curp = $p_tickets = 1;
            }

            $sql = "UPDATE usuarios SET 
                rol = :rol,
                estatus = :estatus,
                permiso_registro_nacimientos = :p_nac,
                permiso_registro_matrimonios = :p_mat,
                permiso_registro_divorcios = :p_div,
                permiso_registro_defunciones = :p_def,
                permiso_registro_inscripciones = :p_ins,
                permiso_registro_reconocimientos = :p_rec,
                permiso_actas_locales = :p_aloc,
                permiso_actas_foraneas = :p_afor,
                permiso_constancias = :p_const,
                permiso_curp = :p_curp,
                permiso_tickets = :p_tick
                WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':rol' => $rol,
                ':estatus' => $estatus,
                ':p_nac' => $p_nacimientos,
                ':p_mat' => $p_matrimonios,
                ':p_div' => $p_divorcios,
                ':p_def' => $p_defunciones,
                ':p_ins' => $p_inscripciones,
                ':p_rec' => $p_reconocimientos,
                ':p_aloc' => $p_actas_locales,
                ':p_afor' => $p_actas_foraneas,
                ':p_const' => $p_constancias,
                ':p_curp' => $p_curp,
                ':p_tick' => $p_tickets,
                ':id' => $id
            ]);

            if ($id === intval($_SESSION['user_id'] ?? 0)) {
                session_regenerate_id(true); // Regenerar ID si se modifican los privilegios del usuario actual
            }

            \Core\Auditoria::logAccion('Administración', 'EDITAR', "Se actualizaron permisos/datos del usuario ID: $id (Rol: $rol)");

            echo json_encode(['status' => 'success']);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Acción no soportada.']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no soportado.']);
}
