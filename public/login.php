<?php
// public/login.php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
]);
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once __DIR__ . '/../core/Auth.php';
$csrf_token = \Core\Auth::generateCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP DRC</title>
    <!-- Assets Locales (No CDN / Offline) -->
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        .login-card {
            background: var(--bg-surface);
            color: var(--text-main);
            padding: 40px;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            color: var(--accent-gold);
            margin-bottom: 10px;
        }
        .login-header h3 {
            font-weight: 700;
            color: var(--text-main);
        }
        body:not(.dark-mode) .login-header h3 {
            color: var(--primary-color);
        }
    </style>
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="fa-solid fa-building-columns"></i>
        <h3>ERP DRC</h3>
        <p class="text-muted">Dirección de Registro Civil</p>
    </div>
    
    <div id="error-message" class="alert alert-danger d-none" role="alert" aria-live="assertive"></div>

    <form id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mb-3">
            <label for="correo" class="form-label fw-bold">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" class="form-control" id="correo" name="correo" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label fw-bold">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            <i class="fa-solid fa-right-to-bracket me-1"></i> Iniciar Sesión
        </button>
    </form>
</div>

<script src="../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'auth.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = 'index.php';
                } else {
                    $('#error-message').removeClass('d-none').text(response.message);
                }
            },
            error: function() {
                $('#error-message').removeClass('d-none').text('Error de conexión con el servidor.');
            }
        });
    });
});
</script>

<script src="../assets/js/global.js"></script>
</body>
</html>
