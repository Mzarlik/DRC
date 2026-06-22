<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_inscripciones');

require_once '../../core/Database.php';
require_once '../../core/Audit.php';
use Core\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim($_POST['numero_acta'] ?? '') === '' || empty($_POST['ciudadano_id']) || trim($_POST['pais_origen'] ?? '') === '' || trim($_POST['documento_extranjero'] ?? '') === '' || trim($_POST['fecha_registro'] ?? '') === '') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellene todos los campos obligatorios.']);
        exit;
    }

    try {
        $pdo = Database::getConnection();

        $numero_acta = mb_strtoupper(trim($_POST['numero_acta'] ?? ''), 'UTF-8');
        $ciudadano_id = intval($_POST['ciudadano_id'] ?? 0);
        $pais_origen = mb_strtoupper(trim($_POST['pais_origen'] ?? ''), 'UTF-8');
        $documento_extranjero = mb_strtoupper(trim($_POST['documento_extranjero'] ?? ''), 'UTF-8');
        $fecha_registro = trim($_POST['fecha_registro'] ?? '');
        $usuario_registro = $_SESSION['user_id'] ?? null;

        $sql = "INSERT INTO inscripciones (numero_acta, ciudadano_id, pais_origen, documento_extranjero, fecha_registro, usuario_registro) 
                         VALUES (:numero_acta, :ciudadano_id, :pais_origen, :documento_extranjero, :fecha_registro, :usuario_registro)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':numero_acta' => $numero_acta,
            ':ciudadano_id' => $ciudadano_id,
            ':pais_origen' => $pais_origen,
            ':documento_extranjero' => $documento_extranjero,
            ':fecha_registro' => $fecha_registro,
            ':usuario_registro' => $usuario_registro
        ]);

        if ($result) {
            \Core\Audit::log('INSERT', 'inscripciones', 'Se registró un nuevo trámite/registro.');
        echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el registro.']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'El número de acta ingresado ya se encuentra registrado.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
