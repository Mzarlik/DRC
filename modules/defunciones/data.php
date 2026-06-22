<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_defunciones');
\Core\Auth::check();

// modules/defunciones/data.php
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
        0 => 'numero_acta',
        1 => 'nombre_completo',
        2 => 'fecha_defuncion',
        3 => 'fecha_registro',
        4 => 'causa_muerte'
    );

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'numero_acta';
    if($columnName === 'nombre_completo') $columnName = 'c.nombre';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    $baseQuery = " FROM defunciones d 
                   INNER JOIN ciudadanos c ON d.ciudadano_id = c.id ";
                   
    $sqlCount = "SELECT COUNT(d.id) as allcount " . $baseQuery;
    $stmtCount = $pdo->query($sqlCount);
    $recordsTotal = $stmtCount->fetchColumn();

    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $searchQuery = " WHERE (d.numero_acta LIKE :search OR c.nombre LIKE :search OR c.apellido_paterno LIKE :search OR d.causa_muerte LIKE :search) ";
        $params[':search'] = '%' . $searchValue . '%';
    }

    $sqlCountFiltered = "SELECT COUNT(d.id) as allcount " . $baseQuery . $searchQuery;
    $stmtCountFiltered = $pdo->prepare($sqlCountFiltered);
    $stmtCountFiltered->execute($params);
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    $sql = "SELECT d.numero_acta, d.fecha_defuncion, d.fecha_registro, d.causa_muerte, 
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
            "numero_acta" => htmlspecialchars($row['numero_acta'], ENT_QUOTES, 'UTF-8'),
            "nombre_completo" => htmlspecialchars($row['nombre_completo'], ENT_QUOTES, 'UTF-8'),
            "fecha_defuncion" => htmlspecialchars($row['fecha_defuncion'], ENT_QUOTES, 'UTF-8'),
            "fecha_registro" => htmlspecialchars($row['fecha_registro'], ENT_QUOTES, 'UTF-8'),
            "causa_muerte" => htmlspecialchars($row['causa_muerte'], ENT_QUOTES, 'UTF-8')
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
