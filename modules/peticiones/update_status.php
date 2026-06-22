<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// Validar que el usuario sea coordinador (ADMIN o SUPERVISOR)
if (!in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'SUPERVISOR'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos de coordinador.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar Token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!\Core\Auth::validateCSRF($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $newStatus = trim($_POST['estatus'] ?? '');
    $accion = trim($_POST['accion'] ?? '');

    if (!$id || !$newStatus) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan campos obligatorios.']);
        exit;
    }

    if (!in_array($newStatus, ['ABIERTA', 'EN_PROGRESO', 'CERRADA'])) {
        echo json_encode(['status' => 'error', 'message' => 'Estatus inválido.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        // Obtener detalles del ticket para auditoría
        $stmtInfo = $pdo->prepare("SELECT folio, tipo_peticion, estatus FROM peticiones WHERE id = :id");
        $stmtInfo->execute([':id' => $id]);
        $ticket = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            echo json_encode(['status' => 'error', 'message' => 'El ticket no existe.']);
            exit;
        }

        $folio = $ticket['folio'];
        $tipo = $ticket['tipo_peticion'];
        $userId = $_SESSION['user_id'];
        
        if ($newStatus === 'CERRADA') {
            $sql = "UPDATE peticiones SET estatus = :estatus, usuario_asignado = :user_id, fecha_cierre = NOW() WHERE id = :id";
        } else {
            $sql = "UPDATE peticiones SET estatus = :estatus, usuario_asignado = :user_id WHERE id = :id";
        }

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':estatus' => $newStatus,
            ':user_id' => $userId,
            ':id' => $id
        ]);

        if ($result) {
            // Log personalizado de auditoría según tipo y acción
            $actionLabel = "Se actualizó el estatus del ticket " . $folio . " a " . $newStatus;
            if ($tipo === 'CORRECCION_ACTA') {
                if ($newStatus === 'CERRADA') {
                    if ($accion === 'RECHAZAR') {
                        $actionLabel = "Se rechazó la aprobación de la corrección de acta para el ticket " . $folio;
                    } else {
                        $actionLabel = "Se aprobó la corrección de acta y se cerró el ticket " . $folio;
                    }
                }
            }
            \Core\Audit::log('UPDATE', 'peticiones', $actionLabel);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'El estatus del ticket ha sido actualizado correctamente.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estatus del ticket.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
