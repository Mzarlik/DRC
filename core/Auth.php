<?php
// core/Auth.php
namespace Core;

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
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ]);
            session_start();
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
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
            </head>
            <body class='bg-light d-flex align-items-center justify-content-center' style='height: 100vh;'>
                <div class='card text-center shadow-sm p-4' style='max-width: 400px;'>
                    <div class='card-body'>
                        <h1 class='text-danger mb-3'><i class='fa-solid fa-triangle-exclamation'></i> 403</h1>
                        <h4 class='card-title mb-3 fw-bold'>Acceso Denegado</h4>
                        <p class='card-text text-muted mb-4'>No tienes permisos suficientes para acceder a este módulo. Contacta al administrador si crees que esto es un error.</p>
                        <a href='/DRC/public/index.php' class='btn btn-primary w-100'>Volver al Dashboard</a>
                    </div>
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
