<?php
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/ciudadanos/search.php
// Endpoint AJAX para Select2 / Tom Select
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

$term = isset($_GET['q']) ? $_GET['q'] : '';
$estado_vital = isset($_GET['estado']) ? $_GET['estado'] : ''; // VIVO o FINADO opcional

try {
    $pdo = Database::getReadConnection();
    
    $sql = "SELECT id, curp, CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS text 
            FROM ciudadanos 
            WHERE estado = 1 AND (nombre LIKE :term OR apellido_paterno LIKE :term OR curp LIKE :term)";
    
    $params = [':term' => '%' . $term . '%'];

    if ($estado_vital !== '') {
        $sql .= " AND estado_vital = :estado";
        $params[':estado'] = $estado_vital;
    }

    $sql .= " LIMIT 30"; // Limitar resultados para rendimiento

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Sanitizar salida
    $sanitized = [];
    foreach($results as $r) {
        $extraInfo = $r['curp'] ? " - CURP: " . htmlspecialchars($r['curp']) : "";
        $sanitized[] = [
            "id" => $r['id'],
            "text" => htmlspecialchars($r['text']) . $extraInfo
        ];
    }

    echo json_encode(["results" => $sanitized]);

} catch (PDOException $e) {
    echo json_encode(["results" => []]);
}
