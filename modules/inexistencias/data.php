<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/inexistencias/data.php
// Script para DataTables Server-Side Processing
header('Content-Type: application/json; charset=utf-8');

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    // Parámetros de DataTables
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $filter_tipo = isset($_GET['tipo_constancia']) ? trim($_GET['tipo_constancia']) : '';

    // Mapeo de columnas para ordenamiento
    $columns = array(
        0 => 'id',
        1 => 'tipo_constancia',
        2 => 'linea_pago',
        3 => 'nombre_completo',
        4 => 'fecha_tramite',
        5 => 'fecha_llegada',
        6 => 'estatus'
    );

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'id';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    // Construcción de la consulta base
    $sql = "SELECT id, tipo_constancia, linea_pago, nombre_completo, fecha_tramite, fecha_llegada, estatus FROM inexistencias";
    $sqlCount = "SELECT COUNT(id) as allcount FROM inexistencias";
    
    // Total sin filtros
    $stmtCount = $pdo->query($sqlCount);
    $recordsTotal = $stmtCount->fetchColumn();

    // Filtros de Búsqueda
    $searchQuery = "";
    $params = [];
    $whereParts = [];

    if ($filter_tipo != '') {
        $whereParts[] = "tipo_constancia = :tipo_constancia";
        $params[':tipo_constancia'] = $filter_tipo;
    }

    if ($searchValue != '') {
        $whereParts[] = "(nombre_completo LIKE :search OR linea_pago LIKE :search OR id LIKE :search)";
        $params[':search'] = '%' . $searchValue . '%';
    }

    if (count($whereParts) > 0) {
        $searchQuery = " WHERE " . implode(" AND ", $whereParts);
    }

    // Total con filtros
    $sqlCountFiltered = "SELECT COUNT(id) as allcount FROM inexistencias" . $searchQuery;
    $stmtCountFiltered = $pdo->prepare($sqlCountFiltered);
    $stmtCountFiltered->execute($params);
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    // Ordenamiento y Paginación
    $sql .= $searchQuery . " ORDER BY " . $columnName . " " . $columnSortOrder . " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bindear parámetros de paginación explícitamente como enteros
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    
    // Bindear parámetros de búsqueda/filtro
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll();

    // Sanitizar salida para evitar XSS (Regla de Testing/Security)
    $sanitizedData = [];
    foreach($data as $row) {
        $sanitizedData[] = [
            "id" => htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'),
            "tipo_constancia" => htmlspecialchars($row['tipo_constancia'], ENT_QUOTES, 'UTF-8'),
            "linea_pago" => htmlspecialchars($row['linea_pago'], ENT_QUOTES, 'UTF-8'), // Mantenido como String
            "nombre_completo" => htmlspecialchars($row['nombre_completo'], ENT_QUOTES, 'UTF-8'),
            "fecha_tramite" => htmlspecialchars($row['fecha_tramite'], ENT_QUOTES, 'UTF-8'),
            "fecha_llegada" => htmlspecialchars($row['fecha_llegada'], ENT_QUOTES, 'UTF-8'),
            "estatus" => htmlspecialchars($row['estatus'], ENT_QUOTES, 'UTF-8')
        ];
    }

    $response = array(
        "draw" => intval($draw),
        "iTotalRecords" => $recordsTotal,
        "iTotalDisplayRecords" => $recordsFiltered,
        "aaData" => $sanitizedData
    );

    echo json_encode($response);

} catch (PDOException $e) {
    // Retornar error generico sin exponer la BD
    echo json_encode([
        "draw" => 0,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "Error interno del servidor al obtener los datos."
    ]);
}
