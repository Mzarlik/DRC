<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_tickets');
\Core\Auth::check();

// modules/peticiones/data.php
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

    $columns = array(
        0 => 'folio',
        1 => 'nombre_completo',
        2 => 'tipo_peticion',
        3 => 'fecha_creacion',
        4 => 'estatus'
    );

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'folio';
    if($columnName === 'nombre_completo') $columnName = 'c.nombre';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    $baseQuery = " FROM peticiones p INNER JOIN ciudadanos c ON p.ciudadano_id = c.id ";
                   
    $sqlCount = "SELECT COUNT(p.id) as allcount " . $baseQuery;
    $stmtCount = $pdo->query($sqlCount);
    $recordsTotal = $stmtCount->fetchColumn();

    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $searchQuery = " WHERE (p.folio LIKE :search OR c.nombre LIKE :search OR c.apellido_paterno LIKE :search OR p.descripcion LIKE :search) ";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $sqlCountFiltered = "SELECT COUNT(p.id) as allcount " . $baseQuery . $searchQuery;
    $stmtCountFiltered = $pdo->prepare($sqlCountFiltered);
    $stmtCountFiltered->execute($params);
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    $sql = "SELECT p.id, p.folio, p.tipo_peticion, DATE(p.fecha_creacion) as fecha_creacion, p.estatus, 
            CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) AS nombre_completo " 
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
            "id" => (int)$row['id'],
            "folio" => htmlspecialchars($row['folio'], ENT_QUOTES, 'UTF-8'),
            "nombre_completo" => htmlspecialchars($row['nombre_completo'], ENT_QUOTES, 'UTF-8'),
            "tipo_peticion" => htmlspecialchars($row['tipo_peticion'], ENT_QUOTES, 'UTF-8'),
            "fecha_creacion" => htmlspecialchars($row['fecha_creacion'], ENT_QUOTES, 'UTF-8'),
            "estatus" => htmlspecialchars($row['estatus'], ENT_QUOTES, 'UTF-8')
        ];
    }

    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $recordsTotal,
        "iTotalDisplayRecords" => $recordsFiltered,
        "aaData" => $sanitizedData
    ]);

} catch (PDOException $e) {
    echo json_encode(["draw" => 0, "iTotalRecords" => 0, "iTotalDisplayRecords" => 0, "aaData" => []]);
}
