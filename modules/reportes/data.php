<?php
// modules/reportes/data.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::check();

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();
    
    // Obtener parámetros de paginación de DataTables
    $draw = (int)($_GET['draw'] ?? 1);
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 10);
    
    // Obtener filtros apilables
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $selected_modulo = $_GET['modulo'] ?? '';
    $estatus = $_GET['estatus'] ?? '';
    $operador_id = $_GET['operador_id'] ?? '';
    
    // Configuración de mapa de consultas
    $modules_map = [
        'inexistencias' => [
            'table' => 'inexistencias inx',
            'modulo_label' => 'Inexistencia',
            'folio_col' => 'inx.linea_pago',
            'ref_col' => 'inx.nombre_completo',
            'fecha_col' => 'inx.fecha_tramite',
            'user_col' => 'inx.usuario_registro',
            'status_col' => 'inx.estatus',
            'joins' => ''
        ],
        'nacimientos' => [
            'table' => 'nacimientos n',
            'modulo_label' => 'Nacimiento',
            'folio_col' => 'n.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'n.fecha_registro',
            'user_col' => 'n.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c ON n.ciudadano_id = c.id'
        ],
        'defunciones' => [
            'table' => 'defunciones d',
            'modulo_label' => 'Defunción',
            'folio_col' => 'd.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'd.fecha_registro',
            'user_col' => 'd.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c ON d.ciudadano_id = c.id'
        ],
        'foraneas' => [
            'table' => 'foraneas f',
            'modulo_label' => 'Foránea',
            'folio_col' => 'f.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'f.fecha_recepcion',
            'user_col' => 'f.usuario_registro',
            'status_col' => 'f.estatus',
            'joins' => 'JOIN ciudadanos c ON f.ciudadano_id = c.id'
        ],
        'peticiones' => [
            'table' => 'peticiones p',
            'modulo_label' => 'Petición / Ticket',
            'folio_col' => 'p.folio',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'DATE(p.fecha_creacion)',
            'user_col' => 'p.usuario_asignado',
            'status_col' => 'p.estatus',
            'joins' => 'JOIN ciudadanos c ON p.ciudadano_id = c.id'
        ],
        'matrimonios' => [
            'table' => 'matrimonios m',
            'modulo_label' => 'Matrimonio',
            'folio_col' => 'm.numero_acta',
            'ref_col' => "CONCAT(c1.nombre, ' & ', c2.nombre)",
            'fecha_col' => 'm.fecha_registro',
            'user_col' => 'm.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id'
        ],
        'divorcios' => [
            'table' => 'divorcios dv',
            'modulo_label' => 'Divorcio',
            'folio_col' => 'dv.numero_acta',
            'ref_col' => "CONCAT(c1.nombre, ' & ', c2.nombre)",
            'fecha_col' => 'dv.fecha_registro',
            'user_col' => 'dv.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c1 ON dv.ciudadano_1_id = c1.id JOIN ciudadanos c2 ON dv.ciudadano_2_id = c2.id'
        ],
        'reconocimientos' => [
            'table' => 'reconocimientos r',
            'modulo_label' => 'Reconocimiento',
            'folio_col' => 'r.numero_acta',
            'ref_col' => "CONCAT(c1.nombre, ' por ', c2.nombre)",
            'fecha_col' => 'r.fecha_registro',
            'user_col' => 'r.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c1 ON r.reconocido_id = c1.id JOIN ciudadanos c2 ON r.reconocedor_id = c2.id'
        ],
        'inscripciones' => [
            'table' => 'inscripciones i',
            'modulo_label' => 'Inscripción',
            'folio_col' => 'i.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'i.fecha_registro',
            'user_col' => 'i.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c ON i.ciudadano_id = c.id'
        ],
        'tramites_curp' => [
            'table' => 'tramites_curp tc',
            'modulo_label' => 'Trámites CURP',
            'folio_col' => "CONCAT('CURP-', tc.id)",
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'tc.fecha_registro',
            'user_col' => 'tc.usuario_registro',
            'status_col' => 'tc.estatus',
            'joins' => 'JOIN ciudadanos c ON tc.ciudadano_id = c.id'
        ]
    ];
    
    $targets = [];
    if (!empty($selected_modulo) && isset($modules_map[$selected_modulo])) {
        $targets[$selected_modulo] = $modules_map[$selected_modulo];
    } else {
        $targets = $modules_map;
    }
    
    $sql_parts = [];
    $params = [];
    
    foreach ($targets as $key => $conf) {
        $select_fields = "'{$conf['modulo_label']}' AS modulo, {$conf['folio_col']} AS folio, {$conf['ref_col']} AS referencia, {$conf['fecha_col']} AS fecha, u.nombre AS operador, {$conf['status_col']} AS estatus";
        
        $where_clauses = ["1=1"];
        
        if (!empty($fecha_inicio)) {
            $where_clauses[] = "{$conf['fecha_col']} >= ?";
            $params[] = $fecha_inicio;
        }
        if (!empty($fecha_fin)) {
            $where_clauses[] = "{$conf['fecha_col']} <= ?";
            $params[] = $fecha_fin;
        }
        if (!empty($operador_id)) {
            $where_clauses[] = "{$conf['user_col']} = ?";
            $params[] = $operador_id;
        }
        if (!empty($estatus)) {
            $where_clauses[] = "{$conf['status_col']} = ?";
            $params[] = $estatus;
        }
        
        $where_str = implode(" AND ", $where_clauses);
        $sql_parts[] = "SELECT {$select_fields} FROM {$conf['table']} {$conf['joins']} LEFT JOIN usuarios u ON {$conf['user_col']} = u.id WHERE {$where_str}";
    }
    
    $union_sql = implode(" UNION ALL ", $sql_parts);
    
    // Obtener la cantidad de registros filtrados (con subconsulta)
    $count_sql = "SELECT COUNT(*) FROM ({$union_sql}) AS temp";
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $recordsFiltered = (int)$stmt_count->fetchColumn();
    
    // Obtener la cantidad total sin filtros
    $total_sql_parts = [];
    foreach ($targets as $key => $conf) {
        $total_sql_parts[] = "SELECT 1 FROM {$conf['table']}";
    }
    $total_sql = "SELECT COUNT(*) FROM (" . implode(" UNION ALL ", $total_sql_parts) . ") AS temp";
    $recordsTotal = (int)$pdo->query($total_sql)->fetchColumn();
    
    // Obtener registros paginados
    $full_sql = $union_sql . " ORDER BY fecha DESC LIMIT {$length} OFFSET {$start}";
    
    $stmt = $pdo->prepare($full_sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "draw" => 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
