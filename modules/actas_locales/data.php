<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_actas_locales');

require_once '../../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();

    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
    $filter_tipo = isset($_GET['tipo_acta']) ? trim($_GET['tipo_acta']) : '';

    // Mapeo de columnas para ordenamiento
    $columns = array(
        0 => 'numero_acta',
        1 => 'tipo_acta',
        2 => 'ciudadano_1',
        3 => 'ciudadano_2',
        4 => 'fecha_registro'
    );

    $columnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $columnName = $columns[$columnIndex] ?? 'fecha_registro';
    $columnSortOrder = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    // Construir sub-queries
    $subqueries = [];
    
    // Nacimientos
    if ($filter_tipo === '' || $filter_tipo === 'NACIMIENTO') {
        $subqueries[] = "SELECT 'NACIMIENTO' AS tipo_acta, n.numero_acta, n.fecha_registro,
                                CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', IFNULL(c.apellido_materno, '')) AS ciudadano_1,
                                '' AS ciudadano_2,
                                c.curp AS curp_1,
                                '' AS curp_2,
                                n.id AS registro_id
                         FROM nacimientos n
                         JOIN ciudadanos c ON n.ciudadano_id = c.id";
    }

    // Matrimonios
    if ($filter_tipo === '' || $filter_tipo === 'MATRIMONIO') {
        $subqueries[] = "SELECT 'MATRIMONIO' AS tipo_acta, m.numero_acta, m.fecha_registro,
                                CONCAT(c1.nombre, ' ', c1.apellido_paterno, ' ', IFNULL(c1.apellido_materno, '')) AS ciudadano_1,
                                CONCAT(c2.nombre, ' ', c2.apellido_paterno, ' ', IFNULL(c2.apellido_materno, '')) AS ciudadano_2,
                                c1.curp AS curp_1,
                                c2.curp AS curp_2,
                                m.id AS registro_id
                         FROM matrimonios m
                         JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id
                         JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id";
    }

    // Divorcios
    if ($filter_tipo === '' || $filter_tipo === 'DIVORCIO') {
        $subqueries[] = "SELECT 'DIVORCIO' AS tipo_acta, d.numero_acta, d.fecha_registro,
                                CONCAT(c1.nombre, ' ', c1.apellido_paterno, ' ', IFNULL(c1.apellido_materno, '')) AS ciudadano_1,
                                CONCAT(c2.nombre, ' ', c2.apellido_paterno, ' ', IFNULL(c2.apellido_materno, '')) AS ciudadano_2,
                                c1.curp AS curp_1,
                                c2.curp AS curp_2,
                                d.id AS registro_id
                         FROM divorcios d
                         JOIN ciudadanos c1 ON d.ciudadano_1_id = c1.id
                         JOIN ciudadanos c2 ON d.ciudadano_2_id = c2.id";
    }

    // Defunciones
    if ($filter_tipo === '' || $filter_tipo === 'DEFUNCION') {
        $subqueries[] = "SELECT 'DEFUNCION' AS tipo_acta, df.numero_acta, df.fecha_registro,
                                CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', IFNULL(c.apellido_materno, '')) AS ciudadano_1,
                                '' AS ciudadano_2,
                                c.curp AS curp_1,
                                '' AS curp_2,
                                df.id AS registro_id
                         FROM defunciones df
                         JOIN ciudadanos c ON df.ciudadano_id = c.id";
    }

    // Reconocimientos
    if ($filter_tipo === '' || $filter_tipo === 'RECONOCIMIENTO') {
        $subqueries[] = "SELECT 'RECONOCIMIENTO' AS tipo_acta, r.numero_acta, r.fecha_registro,
                                CONCAT(c1.nombre, ' ', c1.apellido_paterno, ' ', IFNULL(c1.apellido_materno, '')) AS ciudadano_1,
                                CONCAT(c2.nombre, ' ', c2.apellido_paterno, ' ', IFNULL(c2.apellido_materno, '')) AS ciudadano_2,
                                c1.curp AS curp_1,
                                c2.curp AS curp_2,
                                r.id AS registro_id
                         FROM reconocimientos r
                         JOIN ciudadanos c1 ON r.reconocido_id = c1.id
                         JOIN ciudadanos c2 ON r.reconocedor_id = c2.id";
    }

    // Unir todas las sub-consultas activas
    $unionSql = implode(' UNION ALL ', $subqueries);

    // Consulta de Conteo Total
    $sqlCount = "SELECT COUNT(*) FROM (" . $unionSql . ") AS t";
    $recordsTotal = $pdo->query($sqlCount)->fetchColumn();

    // Filtros de búsqueda sobre la unión
    $searchQuery = "";
    $params = [];
    if ($searchValue != '') {
        $isCurp = preg_match('/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/i', $searchValue);
        if ($isCurp) {
            $encryptedCurp = \Core\Encryption::encrypt(mb_strtoupper($searchValue, 'UTF-8'));
            $searchQuery = " WHERE (numero_acta LIKE :search1 
                                OR ciudadano_1 LIKE :search2 
                                OR ciudadano_2 LIKE :search3 
                                OR curp_1 = :search4 
                                OR curp_2 = :search5)";
            $params[':search1'] = '%' . $searchValue . '%';
            $params[':search2'] = '%' . $searchValue . '%';
            $params[':search3'] = '%' . $searchValue . '%';
            $params[':search4'] = $encryptedCurp;
            $params[':search5'] = $encryptedCurp;
        } else {
            $searchQuery = " WHERE (numero_acta LIKE :search1 
                                OR ciudadano_1 LIKE :search2 
                                OR ciudadano_2 LIKE :search3)";
            $params[':search1'] = '%' . $searchValue . '%';
            $params[':search2'] = '%' . $searchValue . '%';
            $params[':search3'] = '%' . $searchValue . '%';
        }
    }

    // Total Filtrado
    $sqlCountFiltered = "SELECT COUNT(*) FROM (" . $unionSql . ") AS t" . $searchQuery;
    $stmtCountFiltered = $pdo->prepare($sqlCountFiltered);
    foreach ($params as $key => $val) {
        $stmtCountFiltered->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtCountFiltered->execute();
    $recordsFiltered = $stmtCountFiltered->fetchColumn();

    // Ordenamiento y Paginación
    // Para prevenir SQL injection en sort
    if (!in_array($columnSortOrder, ['asc', 'desc'])) $columnSortOrder = 'desc';
    $sql = "SELECT * FROM (" . $unionSql . ") AS t" . $searchQuery . " ORDER BY " . $columnName . " " . $columnSortOrder . " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sanitizedData = [];
    foreach ($records as $row) {
        $sanitizedData[] = [
            "numero_acta" => htmlspecialchars($row['numero_acta'], ENT_QUOTES, 'UTF-8'),
            "tipo_acta" => htmlspecialchars($row['tipo_acta'], ENT_QUOTES, 'UTF-8'),
            "ciudadano_1" => htmlspecialchars($row['ciudadano_1'], ENT_QUOTES, 'UTF-8'),
            "ciudadano_2" => htmlspecialchars($row['ciudadano_2'], ENT_QUOTES, 'UTF-8'),
            "curp_1" => htmlspecialchars(\Core\Encryption::decrypt($row['curp_1']) ?? '', ENT_QUOTES, 'UTF-8'),
            "curp_2" => htmlspecialchars(\Core\Encryption::decrypt($row['curp_2']) ?? '', ENT_QUOTES, 'UTF-8'),
            "fecha_registro" => htmlspecialchars($row['fecha_registro'], ENT_QUOTES, 'UTF-8'),
            "registro_id" => htmlspecialchars($row['registro_id'], ENT_QUOTES, 'UTF-8')
        ];
    }

    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $recordsTotal,
        "iTotalDisplayRecords" => $recordsFiltered,
        "aaData" => $sanitizedData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "draw" => 0,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "Error interno del servidor: " . $e->getMessage()
    ]);
}
