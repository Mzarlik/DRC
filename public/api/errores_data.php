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
        0 => 'e.id',
        1 => 'e.fecha_hora',
        2 => 'u.nombre',
        3 => 'e.mensaje',
        4 => 'e.archivo',
        5 => 'e.url',
        6 => 'e.id'
    ];

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'e.id';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    $baseQuery = " FROM error_logs e LEFT JOIN usuarios u ON e.usuario_id = u.id ";
                   
    $stmtCount = $pdo->query("SELECT COUNT(e.id) as allcount " . $baseQuery);
    $recordsTotal = $stmtCount->fetchColumn();

    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $searchQuery = " WHERE (e.mensaje LIKE :search OR e.archivo LIKE :search OR u.nombre LIKE :search OR e.url LIKE :search) ";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $stmtCountFiltered = $pdo->prepare("SELECT COUNT(e.id) as allcount " . $baseQuery . $searchQuery);
    $stmtCountFiltered->execute($params);
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    $sql = "SELECT e.id, e.fecha_hora, u.nombre as usuario, e.mensaje, e.archivo, e.linea, e.stack_trace, e.url " 
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
            "mensaje" => htmlspecialchars($row['mensaje'] ?? '', ENT_QUOTES, 'UTF-8'),
            "archivo" => htmlspecialchars($row['archivo'] ?? '', ENT_QUOTES, 'UTF-8'),
            "linea" => htmlspecialchars($row['linea'] ?? '', ENT_QUOTES, 'UTF-8'),
            "stack_trace" => htmlspecialchars($row['stack_trace'] ?? '', ENT_QUOTES, 'UTF-8'),
            "url" => htmlspecialchars($row['url'] ?? '', ENT_QUOTES, 'UTF-8')
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
