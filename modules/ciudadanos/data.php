<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/ciudadanos/data.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

try {
    if (!\Core\RateLimiter::check('ciudadanos_data', 60, 60)) {
        http_response_code(429);
        echo json_encode([
            "draw" => 0,
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => [],
            "error" => "Límite de peticiones excedido. Intente de nuevo más tarde."
        ]);
        exit;
    }

    $pdo = Database::getReadConnection();

    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $columns = array(
        0 => 'id',
        1 => 'curp',
        2 => 'nombre',
        3 => 'sexo',
        4 => 'fecha_nacimiento',
        5 => 'estado_vital'
    );

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'id';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    $sql = "SELECT id, curp, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, estado_vital FROM ciudadanos WHERE estado = 1";
    $sqlCount = "SELECT COUNT(id) as allcount FROM ciudadanos WHERE estado = 1";
    
    $stmtCount = $pdo->query($sqlCount);
    $recordsTotal = $stmtCount->fetchColumn();

    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $cleanSearch = trim($searchValue);
        if (preg_match('/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/i', $cleanSearch)) {
            $searchQuery = " AND curp = :search_curp ";
            $params[':search_curp'] = \Core\Encryption::encrypt(mb_strtoupper($cleanSearch, 'UTF-8'));
        } else {
            $searchQuery = " AND (nombre LIKE :search OR apellido_paterno LIKE :search OR apellido_materno LIKE :search) ";
            $params[':search'] = '%' . $searchValue . '%';
        }
    }

    $sqlCountFiltered = "SELECT COUNT(id) as allcount FROM ciudadanos WHERE estado = 1" . $searchQuery;
    $stmtCountFiltered = $pdo->prepare($sqlCountFiltered);
    $stmtCountFiltered->execute($params);
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    $sql .= $searchQuery . " ORDER BY " . $columnName . " " . $columnSortOrder . " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll();

    $sanitizedData = [];
    foreach($data as $row) {
        $sanitizedData[] = [
            "id" => htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'),
            "curp" => htmlspecialchars(\Core\Encryption::decrypt($row['curp']) ?? '', ENT_QUOTES, 'UTF-8'),
            "nombre" => htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'),
            "apellido_paterno" => htmlspecialchars($row['apellido_paterno'], ENT_QUOTES, 'UTF-8'),
            "apellido_materno" => htmlspecialchars($row['apellido_materno'] ?? '', ENT_QUOTES, 'UTF-8'),
            "sexo" => htmlspecialchars($row['sexo'], ENT_QUOTES, 'UTF-8'),
            "fecha_nacimiento" => htmlspecialchars($row['fecha_nacimiento'], ENT_QUOTES, 'UTF-8'),
            "estado_vital" => htmlspecialchars($row['estado_vital'], ENT_QUOTES, 'UTF-8')
        ];
    }

    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $recordsTotal,
        "iTotalDisplayRecords" => $recordsFiltered,
        "aaData" => $sanitizedData
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "draw" => 0,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "Error interno del servidor."
    ]);
}
