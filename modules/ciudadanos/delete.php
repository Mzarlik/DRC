<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!\Core\Auth::validateCSRF($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID de ciudadano inválido.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        // Obtener datos del ciudadano para auditoría
        $stmtInfo = $pdo->prepare("SELECT nombre, apellido_paterno FROM ciudadanos WHERE id = :id");
        $stmtInfo->execute([':id' => $id]);
        $row = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'El ciudadano no existe.']);
            exit;
        }

        $nombreCompleto = $row['nombre'] . ' ' . $row['apellido_paterno'];

        // Realizar baja lógica (Soft Delete)
        $stmt = $pdo->prepare("UPDATE ciudadanos SET estado = 0 WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);

        if ($result) {
            // Guardar auditoría
            \Core\Audit::log('DELETE', 'ciudadanos', 'Baja lógica del ciudadano ID: ' . $id . ' - ' . $nombreCompleto);

            echo json_encode([
                'status' => 'success',
                'message' => 'Ciudadano dado de baja exitosamente del catálogo.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar al ciudadano.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
