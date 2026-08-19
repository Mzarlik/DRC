<?php
namespace Core;

class Auditoria {

    /**
     * Registra una acción en el log de auditoría.
     *
     * @param string $modulo El módulo donde se realiza la acción (ej. 'Nacimientos', 'Usuarios')
     * @param string $accion La acción realizada (ej. 'CREAR', 'EDITAR', 'ELIMINAR')
     * @param string $detalles Descripción detallada de lo que se hizo
     */
    public static function logAccion($modulo, $accion, $detalles = '', $tipoEvento = 'ESCRITURA') {
        try {
            require_once __DIR__ . '/Database.php';
            $pdo = Database::getConnection();
            $usuario_id = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

            if ($usuario_id) {
                $stmt = $pdo->prepare("INSERT INTO auditoria_logs (usuario_id, tipo_evento, modulo, accion, detalles, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$usuario_id, $tipoEvento, $modulo, $accion, $detalles, $ip]);
            }
        } catch (\Exception $e) {
            // Silencioso para no romper la ejecución si el log falla
        }
    }

    /**
     * Registra una consulta de lectura o búsqueda de datos personales (Cumplimiento LGPDPPSO / INAI).
     *
     * @param string $modulo Módulo consultado (ej. 'Ciudadanos', 'Actas')
     * @param string $accion Acción de lectura (ej. 'CONSULTA_CURP', 'BUSQUEDA_EXPEDIENTE')
     * @param string $detalles Criterios de búsqueda o ID del ciudadano consultado
     */
    public static function logLectura($modulo, $accion, $detalles = '') {
        self::logAccion($modulo, $accion, $detalles, 'LECTURA');
    }

    /**
     * Registra un error en el log de errores.
     * Guarda en `mensaje` una versión entendible del error y en `stack_trace`
     * el detalle técnico completo (incluido el mensaje original).
     *
     * @param string $mensaje El mensaje de error principal
     * @param string $archivo El archivo donde ocurrió el error
     * @param int|null $linea La línea donde ocurrió
     * @param string $stack_trace El Stack Trace completo si está disponible
     */
    public static function logError($mensaje, $archivo = '', $linea = null, $stack_trace = '') {
        try {
            require_once __DIR__ . '/Database.php';
            require_once __DIR__ . '/Services/ErrorMessages.php';
            $pdo = Database::getConnection();
            $usuario_id = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $url = $_SERVER['REQUEST_URI'] ?? null;

            $mensajeHumano = \Core\Services\ErrorMessages::humanizeText($mensaje);
            $traceConTecnico = trim($stack_trace . "\n[MENSAJE ORIGINAL] " . $mensaje);

            $stmt = $pdo->prepare("INSERT INTO error_logs (usuario_id, mensaje, archivo, linea, stack_trace, url, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $mensajeHumano, $archivo, $linea, $traceConTecnico, $url, $ip]);
        } catch (\Exception $e) {
            // Silencioso
        }
    }

    /**
     * Manejador de excepciones global
     */
    public static function exceptionHandler(\Throwable $exception) {
        self::logError(
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }

    /**
     * Manejador de errores tradicional de PHP
     */
    public static function errorHandler($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        self::logError(
            $message,
            $file,
            $line,
            'Severidad: ' . $severity
        );
        return false; // Dejar que PHP siga manejando el error
    }
}
