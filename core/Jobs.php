<?php
namespace Core;

/**
 * Clase de apoyo para la cola de trabajos (jobs) de exportaciones.
 * Centraliza el lanzamiento asíncrono del worker CLI de forma portable
 * (Windows con "start /B" / Unix con "nohup ... &") y resuelve el binario
 * de PHP desde .env (PHP_BIN), XAMPP o PHP_BINARY del entorno.
 */
class Jobs {
    /**
     * Resuelve la ruta del binario de PHP ejecutable en CLI.
     * Prioridad: PHP_BIN del .env > C:\xampp\php\php.exe > PHP_BINARY > 'php'.
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

        // Si estamos en Windows con XAMPP común
        if (PHP_OS_FAMILY === 'Windows') {
            $xamppPhp = 'C:\\xampp\\php\\php.exe';
            if (file_exists($xamppPhp)) {
                return $xamppPhp;
            }
        }

        // Si PHP_BINARY es un binario CLI válido (no httpd.exe del servidor web)
        if (defined('PHP_BINARY') && PHP_BINARY !== '' && PHP_BINARY !== false) {
            $bin = strtolower(PHP_BINARY);
            if (str_contains($bin, 'php') && !str_contains($bin, 'httpd') && !str_contains($bin, 'apache')) {
                return PHP_BINARY;
            }
        }

        return 'php';
    }

    /**
     * Lanza core/Worker.php en segundo plano sin bloquear la petición web.
     *
     * @return bool True si el proceso pudo lanzarse
     */
    public static function launchWorker() {
        $phpBin = self::getPhpBinary();
        $workerScript = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Worker.php';

        if (PHP_OS_FAMILY === 'Windows') {
            if (class_exists('COM')) {
                try {
                    $wsh = new \COM("WScript.Shell");
                    $wsh->Run(escapeshellarg($phpBin) . ' ' . escapeshellarg($workerScript), 0, false);
                    return true;
                } catch (\Throwable $e) {
                    // Fallback
                }
            }
            $cmd = 'cmd.exe /c start /min "" ' . escapeshellarg($phpBin) . ' ' . escapeshellarg($workerScript);
            $p = @popen($cmd, 'r');
            if ($p) {
                @pclose($p);
                return true;
            }
            @exec($cmd);
            return true;
        } else {
            $cmd = 'nohup ' . escapeshellarg($phpBin) . ' ' . escapeshellarg($workerScript) . ' > /dev/null 2>&1 &';
            $p = @popen($cmd, 'r');
            if ($p) {
                @pclose($p);
                return true;
            }
        }
        return false;
    }
}