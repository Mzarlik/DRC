<?php
namespace Core;

/**
 * Clase de apoyo para la cola de trabajos (jobs) de exportaciones.
 * Centraliza el lanzamiento asíncrono del worker CLI de forma portable
 * (Windows con "start /B" / Unix con "nohup ... &") y resuelve el binario
 * de PHP desde .env (PHP_BIN) o PHP_BINARY del entorno.
 */
class Jobs {
    /**
     * Resuelve la ruta del binario de PHP.
     * Prioridad: PHP_BIN del .env > constante PHP_BINARY > 'php' (PATH).
     *
     * @return string
     */
    public static function getPhpBinary() {
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            $env = @parse_ini_file($envPath);
            if ($env !== false && isset($env['PHP_BIN']) && trim($env['PHP_BIN']) !== '') {
                return trim($env['PHP_BIN']);
            }
        }
        if (defined('PHP_BINARY') && PHP_BINARY !== '' && PHP_BINARY !== false) {
            return PHP_BINARY;
        }
        return 'php';
    }

    /**
     * Lanza core/Worker.php en segundo plano sin bloquear la petición web.
     *
     * @return bool True si el proceso pudo lanzarse
     */
    public static function launchWorker() {
        $php = escapeshellarg(self::getPhpBinary());
        $worker = escapeshellarg(dirname(__DIR__) . '/core/Worker.php');
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "start /B " . $php . " " . $worker . " > NUL 2>&1";
        } else {
            $cmd = "nohup " . $php . " " . $worker . " > /dev/null 2>&1 &";
        }
        $handle = @popen($cmd, 'r');
        if ($handle) {
            @pclose($handle);
            return true;
        }
        return false;
    }
}