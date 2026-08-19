<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();
    $draw = intval($_GET['draw'] ?? 0);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $search = trim($_GET['search']['value'] ?? '');
    $orderCol = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = ($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $cols = ['pv.folio', 'pv.solicitante_nombre', 'pv.tipo_peticion', 'pv.detalle', 'pv.estatus', 'pv.creado_en'];
    $orderSql = isset($cols[$orderCol]) ? $cols[$orderCol] . ' ' . $orderDir : 'pv.id DESC';

    $where = 'WHERE pv.deleted_at IS NULL';
    $params = [];
    if ($search !== '') {
        $where .= " AND (pv.folio LIKE :s1 OR pv.solicitante_nombre LIKE :s2 OR pv.solicitante_curp LIKE :s3 OR pv.tipo_peticion LIKE :s4 OR pv.detalle LIKE :s5 OR pv.estatus LIKE :s6)";
        $term = '%' . $search . '%';
        $params[':s1'] = $term;
        $params[':s2'] = $term;
        $params[':s3'] = $term;
        $params[':s4'] = $term;
        $params[':s5'] = $term;
        $params[':s6'] = $term;
    }

    $total = (int)$pdo->query("SELECT COUNT(*) FROM peticiones_ventanilla WHERE deleted_at IS NULL")->fetchColumn();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM peticiones_ventanilla pv $where");
    $stmtCount->execute($params);
    $filtered = (int)$stmtCount->fetchColumn();

    $stmt = $pdo->prepare("SELECT pv.id, pv.folio, pv.solicitante_nombre, pv.solicitante_curp, pv.solicitante_telefono,
                                   pv.tipo_peticion, pv.detalle, pv.estatus, pv.creado_en
                            FROM peticiones_ventanilla pv
                            $where
                            ORDER BY $orderSql
                            LIMIT $length OFFSET $start");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => $draw,
        'iTotalRecords' => $total,
        'iTotalDisplayRecords' => $filtered,
        'aaData' => $rows
    ]);
} catch (\Throwable $e) {
    error_log('peticion_rapida/data: ' . $e->getMessage());
    echo json_encode(['draw' => intval($_GET['draw'] ?? 0), 'iTotalRecords' => 0, 'iTotalDisplayRecords' => 0, 'aaData' => []]);
}
