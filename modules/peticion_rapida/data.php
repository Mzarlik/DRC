<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/data.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getReadConnection();
    $draw = intval($_GET['draw'] ?? 0);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $search = trim($_GET['search']['value'] ?? '');
    $orderCol = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = ($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $cols = ['pv.folio', 'CONCAT_WS(\' \', c.nombre, c.apellido_paterno, c.apellido_materno)', 'pv.tipo_peticion', 'pv.detalle', 'pv.estatus', 'pv.creado_en'];
    $orderSql = isset($cols[$orderCol]) ? $cols[$orderCol] . ' ' . $orderDir : 'pv.creado_en DESC';

    $where = 'WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= " AND (pv.folio LIKE :s OR pv.tipo_peticion LIKE :s OR pv.detalle LIKE :s OR pv.estatus LIKE :s OR CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) LIKE :s)";
        $params[':s'] = '%' . $search . '%';
    }

    $total = (int)$pdo->query("SELECT COUNT(*) FROM peticiones_ventanilla pv")->fetchColumn();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM peticiones_ventanilla pv LEFT JOIN ciudadanos c ON pv.ciudadano_id = c.id $where");
    $stmtCount->execute($params);
    $filtered = (int)$stmtCount->fetchColumn();

    $stmt = $pdo->prepare("SELECT pv.id, pv.folio, pv.tipo_peticion, pv.detalle, pv.estatus, pv.creado_en,
                                  CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) AS ciudadano
                           FROM peticiones_ventanilla pv
                           LEFT JOIN ciudadanos c ON pv.ciudadano_id = c.id
                           $where
                           ORDER BY $orderSql
                           LIMIT $length OFFSET $start");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

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
