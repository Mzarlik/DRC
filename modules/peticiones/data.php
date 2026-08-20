<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_tickets');
\Core\Auth::check();

// modules/peticiones/data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Encryption.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();
    $draw = intval($_GET['draw'] ?? 0);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $search = trim($_GET['search']['value'] ?? '');
    $orderCol = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = ($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $cols = ['p.folio', 'c.nombre', 'p.tipo_peticion', 'p.descripcion', 'p.estatus', 'p.fecha_creacion'];
    $orderSql = isset($cols[$orderCol]) ? $cols[$orderCol] . ' ' . $orderDir : 'p.id DESC';

    $where = 'WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= " AND (p.folio LIKE :s1 OR c.nombre LIKE :s2 OR c.apellido_paterno LIKE :s3 OR p.tipo_peticion LIKE :s4 OR p.descripcion LIKE :s5 OR p.estatus LIKE :s6)";
        $term = '%' . $search . '%';
        $params[':s1'] = $term;
        $params[':s2'] = $term;
        $params[':s3'] = $term;
        $params[':s4'] = $term;
        $params[':s5'] = $term;
        $params[':s6'] = $term;
    }

    $total = (int)$pdo->query("SELECT COUNT(*) FROM peticiones")->fetchColumn();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM peticiones p INNER JOIN ciudadanos c ON p.ciudadano_id = c.id $where");
    $stmtCount->execute($params);
    $filtered = (int)$stmtCount->fetchColumn();

    $stmt = $pdo->prepare("SELECT p.id, p.folio, p.tipo_peticion, p.descripcion, p.estatus, p.fecha_creacion,
                                   c.nombre, c.apellido_paterno, c.apellido_materno, c.curp
                            FROM peticiones p
                            INNER JOIN ciudadanos c ON p.ciudadano_id = c.id
                            $where
                            ORDER BY $orderSql
                            LIMIT $length OFFSET $start");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Desencriptar CURP para visualización
    foreach ($rows as &$r) {
        if (!empty($r['curp'])) {
            $r['curp'] = \Core\Encryption::decrypt($r['curp']);
        }
    }

    echo json_encode([
        'draw' => $draw,
        'iTotalRecords' => $total,
        'iTotalDisplayRecords' => $filtered,
        'aaData' => $rows
    ]);
} catch (\Throwable $e) {
    error_log('peticiones/data: ' . $e->getMessage());
    echo json_encode(['draw' => intval($_GET['draw'] ?? 0), 'iTotalRecords' => 0, 'iTotalDisplayRecords' => 0, 'aaData' => []]);
}
