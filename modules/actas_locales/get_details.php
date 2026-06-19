<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_actas_locales');

require_once '../../core/Database.php';
use Core\Database;

$tipo = $_GET['tipo'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$tipo || !$id) {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros insuficientes.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    switch ($tipo) {
        case 'NACIMIENTO':
            $stmt = $pdo->prepare("SELECT n.id, n.numero_acta, n.lugar_nacimiento, n.fecha_registro,
                                          c.nombre AS c_nombre, c.apellido_paterno AS c_app, c.apellido_materno AS c_apm, c.curp AS c_curp, c.fecha_nacimiento AS c_fnac, c.sexo AS c_sexo,
                                          p.nombre AS p_nombre, p.apellido_paterno AS p_app, p.apellido_materno AS p_apm, p.curp AS p_curp,
                                          m.nombre AS m_nombre, m.apellido_paterno AS m_app, m.apellido_materno AS m_apm, m.curp AS m_curp
                                   FROM nacimientos n
                                   JOIN ciudadanos c ON n.ciudadano_id = c.id
                                   LEFT JOIN ciudadanos p ON n.padre_id = p.id
                                   LEFT JOIN ciudadanos m ON n.madre_id = m.id
                                   WHERE n.id = :id");
            break;
            
        case 'MATRIMONIO':
            $stmt = $pdo->prepare("SELECT m.id, m.numero_acta, m.regimen_patrimonial, m.fecha_registro,
                                          c1.nombre AS c1_nombre, c1.apellido_paterno AS c1_app, c1.apellido_materno AS c1_apm, c1.curp AS c1_curp,
                                          c2.nombre AS c2_nombre, c2.apellido_paterno AS c2_app, c2.apellido_materno AS c2_apm, c2.curp AS c2_curp
                                   FROM matrimonios m
                                   JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id
                                   JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id
                                   WHERE m.id = :id");
            break;
            
        case 'DIVORCIO':
            $stmt = $pdo->prepare("SELECT d.id, d.numero_acta, d.tipo_divorcio, d.fecha_registro,
                                          c1.nombre AS c1_nombre, c1.apellido_paterno AS c1_app, c1.apellido_materno AS c1_apm, c1.curp AS c1_curp,
                                          c2.nombre AS c2_nombre, c2.apellido_paterno AS c2_app, c2.apellido_materno AS c2_apm, c2.curp AS c2_curp
                                   FROM divorcios d
                                   JOIN ciudadanos c1 ON d.ciudadano_1_id = c1.id
                                   JOIN ciudadanos c2 ON d.ciudadano_2_id = c2.id
                                   WHERE d.id = :id");
            break;
            
        case 'DEFUNCION':
            $stmt = $pdo->prepare("SELECT df.id, df.numero_acta, df.fecha_defuncion, df.causa_muerte, df.fecha_registro,
                                          c.nombre AS c_nombre, c.apellido_paterno AS c_app, c.apellido_materno AS c_apm, c.curp AS c_curp
                                   FROM defunciones df
                                   JOIN ciudadanos c ON df.ciudadano_id = c.id
                                   WHERE df.id = :id");
            break;
            
        case 'RECONOCIMIENTO':
            $stmt = $pdo->prepare("SELECT r.id, r.numero_acta, r.fecha_registro,
                                          c1.nombre AS c1_nombre, c1.apellido_paterno AS c1_app, c1.apellido_materno AS c1_apm, c1.curp AS c1_curp,
                                          c2.nombre AS c2_nombre, c2.apellido_paterno AS c2_app, c2.apellido_materno AS c2_apm, c2.curp AS c2_curp
                                   FROM reconocimientos r
                                   JOIN ciudadanos c1 ON r.reconocido_id = c1.id
                                   JOIN ciudadanos c2 ON r.reconocedor_id = c2.id
                                   WHERE r.id = :id");
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Tipo de acta no soportado.']);
            exit;
    }
    
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $sanitized = [];
        foreach ($data as $k => $v) {
            $sanitized[$k] = htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
        }
        echo json_encode(['status' => 'success', 'data' => $sanitized]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró el registro.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
