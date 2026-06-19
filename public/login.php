<?php
// public/login.php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        .login-header h3 {
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="fa-solid fa-building-columns"></i>
        <h3>ERP DRC</h3>
        <p class="text-muted">Dirección de Registro Civil</p>
    </div>
    
    <div id="error-message" class="alert alert-danger d-none"></div>

    <form id="loginForm">
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
        <button type="submit" class="btn btn-primary w-100" style="background: var(--secondary-color); border: none; padding: 12px; font-weight: 600;">
            Iniciar Sesión
        </button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

</body>
</html>
