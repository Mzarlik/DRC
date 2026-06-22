<?php
// core/Audit.php
namespace Core;

class Audit {
    /**
     * Registra una acción en la bitácora de auditoría.
     * 
     * @param string $accion INSERT, UPDATE, DELETE u otra acción descriptiva.
     * @param string $modulo El nombre del módulo donde ocurre la acción.
     * @param string $detalles Detalles adicionales opcionales.
     */
    public static function log($accion, $modulo, $detalles = '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO bitacora_auditoria (usuario_id, accion, modulo, detalles, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $accion,
                $modulo,
                $detalles,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (\PDOException $e) {
            // Se puede registrar el error en un archivo de log de errores general,
            // pero no debe interrumpir el flujo principal para no afectar la experiencia del usuario.
            error_log("Error al registrar auditoría: " . $e->getMessage());
        }
    }
}
