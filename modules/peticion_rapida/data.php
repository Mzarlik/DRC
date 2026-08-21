<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Database.php';
use Core\Database;
use Core\Encryption;

try {
    $pdo = Database::getReadConnection();
    $draw = intval($_GET['draw'] ?? 0);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $search = trim($_GET['search']['value'] ?? '');
    $orderCol = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = ($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    // Detectar si ya existe la columna de blind index (migración opcional)
    $hasBindex = false;
    try {
        $pdo->query("SELECT solicitante_curp_bindex FROM peticiones_ventanilla LIMIT 0");
        $hasBindex = true;
    } catch (\Throwable $e) {
        $hasBindex = false;
    }

    $cols = ['pv.folio', 'pv.solicitante_nombre', 'pv.tipo_peticion', 'pv.detalle', 'pv.estatus', 'pv.creado_en'];
    $orderSql = isset($cols[$orderCol]) ? $cols[$orderCol] . ' ' . $orderDir : 'pv.id DESC';

    $where = 'WHERE pv.deleted_at IS NULL';
    $params = [];
    if ($search !== '') {
        // Si el término parece una CURP completa, buscar por blind index (búsqueda exacta cifrada)
        $curpCandidata = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $search));
        $esCurpCompleta = preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/', $curpCandidata) === 1;
        if ($hasBindex && $esCurpCompleta) {
            $where .= " AND pv.solicitante_curp_bindex = :scurp";
            $params[':scurp'] = Encryption::getBlindIndex($curpCandidata);
        } else {
            // Búsqueda parcial por campos de texto libre (la CURP cifrada no admite LIKE)
            $where .= " AND (pv.folio LIKE :s1 OR pv.solicitante_nombre LIKE :s2 OR pv.tipo_peticion LIKE :s4 OR pv.detalle LIKE :s5 OR pv.estatus LIKE :s6)";
            $term = '%' . $search . '%';
            $params += [':s1' => $term, ':s2' => $term, ':s4' => $term, ':s5' => $term, ':s6' => $term];
        }
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

    // Descifrar la CURP del solicitante para su visualización autorizada
    foreach ($rows as &$row) {
        if (!empty($row['solicitante_curp'])) {
            $descifrada = Encryption::decrypt($row['solicitante_curp']);
            // Si el valor descifrado coincide con formato CURP se usa; si no (legado en claro), se conserva.
            $row['solicitante_curp'] = preg_match('/^[A-Z]{18}$/', $descifrada) ? $descifrada : $row['solicitante_curp'];
        }
    }
    unset($row);

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
