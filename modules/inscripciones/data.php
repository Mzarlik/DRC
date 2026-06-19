<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_inscripciones');

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    // Sort
    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    $sql = "SELECT i.id, i.numero_acta, i.pais_origen, i.fecha_registro,
                                CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', IFNULL(c.apellido_materno, '')) AS ciudadano
                         FROM inscripciones i
                         JOIN ciudadanos c ON i.ciudadano_id = c.id";
    $sqlCount = "SELECT COUNT(*) FROM inscripciones";

    $totalRecords = $pdo->query($sqlCount)->fetchColumn();

    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $searchQuery = " WHERE (i.numero_acta LIKE :search OR i.pais_origen LIKE :search OR c.nombre LIKE :search OR c.apellido_paterno LIKE :search) ";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $sqlFilteredCount = "SELECT COUNT(*) FROM inscripciones m ";
    $sqlFilteredCount .= " JOIN ciudadanos c ON m.ciudadano_id = c.id ";
    $sqlFilteredCount .= $searchQuery;
    $stmtFiltered = $pdo->prepare($sqlFilteredCount);
    $stmtFiltered->execute($params);
    $totalRecordwithFilter = $stmtFiltered->fetchColumn();

    $columns = ['numero_acta', 'ciudadano', 'pais_origen', 'fecha_registro'];
    $columnName = $columns[$columnIndex] ?? 'id';
    // Prevent injection in column sorting
    if (!in_array($columnSortOrder, ['asc', 'desc'])) $columnSortOrder = 'desc';

    // Add search, sorting and paging
    $sql .= $searchQuery . " ORDER BY " . ($columnName === 'contrayente_1' || $columnName === 'divorciado_1' || $columnName === 'reconocido' || $columnName === 'ciudadano' ? '1' : $columnName) . " " . $columnSortOrder . " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();
    $records = $stmt->fetchAll();

    $data = [];
    foreach ($records as $row) {
        $sanitized = [];
        foreach ($row as $k => $v) {
            $sanitized[$k] = htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
        }
        $data[] = $sanitized;
    }

    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => $totalRecordwithFilter,
        "aaData" => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        "draw" => 0,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => $e->getMessage()
    ]);
}
