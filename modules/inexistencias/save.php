<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');
\Core\Auth::check();

// modules/inexistencias/save.php
header('Content-Type: application/json; charset=utf-8');

require_once '../../core/Database.php';
require_once '../../core/Audit.php';

use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validar CSRF (Aquí se simula, en un entorno real habría verificación contra sesión)
    if (empty($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido o faltante.']);
        exit;
    }

    // 2. Sanitizar y capturar entradas
    $linea_pago = trim($_POST['linea_pago'] ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $fecha_tramite = trim($_POST['fecha_tramite'] ?? '');
    $fecha_llegada = trim($_POST['fecha_llegada'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $tipo_constancia = trim($_POST['tipo_constancia'] ?? 'INEXISTENCIA_NACIMIENTO');

    // 3. Aplicar Reglas de Negocio Estrictas
    // Convertir a mayúsculas
    $nombre_completo = mb_strtoupper($nombre_completo, 'UTF-8');
    $observaciones = mb_strtoupper($observaciones, 'UTF-8');
    
    // Validación de tipo de constancia
    $tipos_validos = ['INEXISTENCIA_NACIMIENTO', 'INEXISTENCIA_MATRIMONIO', 'INEXISTENCIA_DESCENDENCIA', 'NO_DEUDOR'];
    if (!in_array($tipo_constancia, $tipos_validos)) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo de constancia inválido.']);
        exit;
    }

    // Validación de longitud y formato de cadena para la línea de pago
    if (strlen($linea_pago) < 17 || strlen($linea_pago) > 25) {
        echo json_encode(['status' => 'error', 'message' => 'La línea de pago debe tener entre 17 y 25 caracteres.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        // Validación Cruzada: Verificar si ya existe en ciudadanos
        if ($tipo_constancia === 'INEXISTENCIA_NACIMIENTO') {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM ciudadanos WHERE CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE :nombre");
            $stmtCheck->execute([':nombre' => '%' . $nombre_completo . '%']);
            $exists = $stmtCheck->fetchColumn();

            if ($exists > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Se detectó un registro local previo para este nombre en el Catálogo de Ciudadanos. No es posible expedir la constancia.']);
                exit;
            }
        }

        // 4. Inserción con Sentencia Preparada (Prevención de SQL Injection)
        $sql = "INSERT INTO inexistencias (tipo_constancia, linea_pago, fecha_tramite, fecha_llegada, nombre_completo, observaciones) 
                VALUES (:tipo_constancia, :linea_pago, :fecha_tramite, :fecha_llegada, :nombre_completo, :observaciones)";
        
        $stmt = $pdo->prepare($sql);
        
        // Ejecución
        $result = $stmt->execute([
            ':tipo_constancia' => $tipo_constancia,
            ':linea_pago' => $linea_pago, // Pasado como String
            ':fecha_tramite' => $fecha_tramite,
            ':fecha_llegada' => $fecha_llegada,
            ':nombre_completo' => $nombre_completo,
            ':observaciones' => $observaciones
        ]);

        if ($result) {
            \Core\Audit::log('INSERT', 'inexistencias', 'Se registró un nuevo trámite/registro.');
        echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro en la base de datos.']);
        }

    } catch (PDOException $e) {
        // En un caso real: $e->getMessage() se guarda en LOG y no se expone al cliente
        // Validar si el error es de clave única (ej. línea de pago duplicada)
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'La línea de pago ingresada ya se encuentra registrada en el sistema.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error de integridad en la base de datos.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
