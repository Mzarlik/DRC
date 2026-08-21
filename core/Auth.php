<?php
// core/Auth.php
namespace Core;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Auditoria.php';

// Registrar manejadores globales de errores y excepciones
set_exception_handler(['\Core\Auditoria', 'exceptionHandler']);
set_error_handler(['\Core\Auditoria', 'errorHandler']);

class Auth {
    /**
     * Configura parámetros seguros de cookie de sesión antes de iniciarla.
     * HttpOnly, SameSite=Lax y Secure cuando la petición llega por HTTPS.
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @ini_set('session.use_strict_mode', '1');
            @session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ]);
            @session_start();
        } elseif (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public static function check() {
        self::initSession();
        
        // Permitir bypass SOLO para tareas programadas (cron) vía token, y únicamente
        // cuando el script se ejecuta desde CLI y el token coincide (comparación constante).
        $envPath = dirname(__DIR__) . '/.env';
        if (php_sapi_name() === 'cli' && file_exists($envPath)) {
            $env = @parse_ini_file($envPath);
            if ($env !== false && isset($env['CRON_SECRET']) && isset($_GET['cron_token'])
                && is_string($env['CRON_SECRET']) && hash_equals($env['CRON_SECRET'], $_GET['cron_token'])) {
                return;
            }
        }
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: /DRC/public/login.php");
            exit;
        }
    }

    public static function getUserName() {
        self::initSession();
        return $_SESSION['user_nombre'] ?? 'Usuario';
    }

    /**
     * Verifica si el usuario logueado tiene un permiso específico.
     * Los administradores tienen acceso a todo.
     */
    public static function hasPermission($permissionName) {
        self::initSession();

        if (($_SESSION['user_rol'] ?? '') === 'ADMIN') {
            return true;
        }

        return isset($_SESSION[$permissionName]) && (int)$_SESSION[$permissionName] === 1;
    }

    /**
     * Indica si el usuario es coordinador (ADMIN, COORDINADOR o SUPERVISOR).
     */
    public static function esCoordinador() {
        self::initSession();
        return in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'COORDINADOR', 'SUPERVISOR'], true);
    }

    /**
     * Autoriza la exportación a Excel: la permite únicamente a coordinadores
     * o a usuarios con la bandera 'permiso_exportar' habilitada.
     */
    public static function canExportar() {
        return self::esCoordinador() || self::hasPermission('permiso_exportar');
    }

    /**
     * Guarda de endpoints de exportación: exige coordinador o bandera
     * permiso_exportar y deja registro en la auditoría de quien lo solicitó.
     */
    public static function checkExport($modulo = 'Desconocido') {
        self::check();
        if (!self::canExportar()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'No autorizado para exportar. Solicite el permiso de exportación al coordinador o administrador.']);
            exit;
        }
        \Core\Auditoria::logAccion('Exportaciones', 'EXPORTAR', "Exportación a Excel solicitada: $modulo.");
        return true;
    }

    /**
     * Protege una vista verificando que el usuario tenga el permiso adecuado.
     * Si no lo tiene, interrumpe con error 403.
     */
    public static function checkPermission($permissionName) {
        self::check();
        if (!self::hasPermission($permissionName)) {
            header("HTTP/1.1 403 Forbidden");
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Acceso Denegado - ERP DRC</title>
                <style>
                    body { margin: 0; background: #f8f9fa; font-family: system-ui, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                    .card-403 { background: #fff; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,.08); padding: 2rem; max-width: 400px; text-align: center; }
                    .card-403 .code { color: #dc3545; font-size: 3rem; font-weight: 700; margin: 0 0 .5rem; }
                    .card-403 h1 { font-size: 1.25rem; margin: 0 0 .75rem; color: #212529; }
                    .card-403 p { color: #6c757d; margin: 0 0 1.5rem; }
                    .card-403 a { display: block; background: #6d1a36; color: #fff; text-decoration: none; padding: .65rem; border-radius: 6px; font-weight: 600; }
                    .card-403 a:hover { filter: brightness(1.1); }
                </style>
            </head>
            <body>
                <div class='card-403'>
                    <p class='code'>&#9888; 403</p>
                    <h1>Acceso Denegado</h1>
                    <p>No tienes permisos suficientes para acceder a este m&oacute;dulo. Contacta al administrador si crees que esto es un error.</p>
                    <a href='/DRC/public/index.php'>Volver al Dashboard</a>
                </div>
            </body>
            </html>";
            exit;
        }
    }

    /**
     * Genera un token CSRF seguro y lo almacena en sesión.
     */
    public static function generateCSRF() {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida que el token proporcionado coincida con el de la sesión.
     */
    public static function validateCSRF($token) {
        self::initSession();
        if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
}
