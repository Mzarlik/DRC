<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

if (($_SESSION['user_rol'] ?? '') !== 'ADMIN') {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $columns = [
        0 => 'a.id',
        1 => 'a.fecha_hora',
        2 => 'u.nombre',
        3 => 'a.modulo',
        4 => 'a.accion',
        5 => 'a.detalles',
        6 => 'a.ip_address'
    ];

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'a.id';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    $baseQuery = " FROM auditoria_logs a LEFT JOIN usuarios u ON a.usuario_id = u.id ";
                   
    $stmtCount = $pdo->query("SELECT COUNT(a.id) as allcount " . $baseQuery);
    $recordsTotal = $stmtCount->fetchColumn();

    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $searchQuery = " WHERE (a.modulo LIKE :search OR a.accion LIKE :search OR a.detalles LIKE :search OR u.nombre LIKE :search OR a.ip_address LIKE :search) ";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $stmtCountFiltered = $pdo->prepare("SELECT COUNT(a.id) as allcount " . $baseQuery . $searchQuery);
    $stmtCountFiltered->execute($params);
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    $sql = "SELECT a.id, a.fecha_hora, u.nombre as usuario, a.modulo, a.accion, a.detalles, a.ip_address " 
            . $baseQuery . $searchQuery . " ORDER BY " . $columnName . " " . $columnSortOrder . " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    
    if ($searchValue != '') {
        $stmt->bindValue(':search', '%' . $searchValue . '%', PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll();

    $sanitizedData = [];
    foreach($data as $row) {
        $sanitizedData[] = [
            "id" => $row['id'],
            "fecha_hora" => htmlspecialchars($row['fecha_hora'] ?? '', ENT_QUOTES, 'UTF-8'),
            "usuario" => htmlspecialchars($row['usuario'] ?? 'Sistema', ENT_QUOTES, 'UTF-8'),
            "modulo" => htmlspecialchars($row['modulo'] ?? '', ENT_QUOTES, 'UTF-8'),
            "accion" => htmlspecialchars($row['accion'] ?? '', ENT_QUOTES, 'UTF-8'),
            "detalles" => htmlspecialchars($row['detalles'] ?? '', ENT_QUOTES, 'UTF-8'),
            "ip_address" => htmlspecialchars($row['ip_address'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }

    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $recordsTotal,
        "iTotalDisplayRecords" => $recordsFiltered,
        "aaData" => $sanitizedData
    ]);

} catch (\PDOException $e) {
    echo json_encode([
        "draw" => 0,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "Error al obtener datos."
    ]);
}
