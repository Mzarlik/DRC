<?php
// core/Auth.php
namespace Core;

class Auth {
    public static function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: /DRC/public/login.php");
            exit;
        }
    }

    public static function getUserName() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_nombre'] ?? 'Usuario';
    }

    /**
     * Verifica si el usuario logueado tiene un permiso específico.
     * Los administradores tienen acceso a todo.
     */
    public static function hasPermission($permissionName) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (($_SESSION['user_rol'] ?? '') === 'ADMIN') {
            return true;
        }

        return isset($_SESSION[$permissionName]) && (int)$_SESSION[$permissionName] === 1;
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida que el token proporcionado coincida con el de la sesión.
     */
    public static function validateCSRF($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
}
