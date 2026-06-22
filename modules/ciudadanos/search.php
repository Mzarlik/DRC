<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/ciudadanos/search.php
// Endpoint AJAX para Select2 / Tom Select
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
use Core\Database;

$term = isset($_GET['q']) ? $_GET['q'] : '';
$estado_vital = isset($_GET['estado']) ? $_GET['estado'] : ''; // VIVO o FINADO opcional

if (!\Core\RateLimiter::check('ciudadanos_search', 30, 60)) {
    http_response_code(429);
    echo json_encode(['results' => [], 'status' => 'error', 'message' => 'Límite de peticiones excedido. Intente de nuevo más tarde.']);
    exit;
}

try {
    $pdo = Database::getReadConnection();
    
    $params = [];
    $termClean = trim($term);
    
    // Si coincide con el formato de un CURP completo, hacer búsqueda exacta con encriptación determinista
    if (preg_match('/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/i', $termClean)) {
        $sql = "SELECT id, curp, CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS text 
                FROM ciudadanos 
                WHERE estado = 1 AND curp = :exact_curp";
        $params[':exact_curp'] = \Core\Encryption::encrypt(mb_strtoupper($termClean, 'UTF-8'));
    } else {
        $sql = "SELECT id, curp, CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS text 
                FROM ciudadanos 
                WHERE estado = 1 AND (nombre LIKE :term OR apellido_paterno LIKE :term)";
        $params[':term'] = '%' . $termClean . '%';
    }

    if ($estado_vital !== '') {
        $sql .= " AND estado_vital = :estado";
        $params[':estado'] = $estado_vital;
    }

    $sql .= " LIMIT 30"; // Limitar resultados para rendimiento

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Sanitizar y desencriptar salida
    $sanitized = [];
    foreach($results as $r) {
        $decryptedCurp = \Core\Encryption::decrypt($r['curp']);
        $extraInfo = $decryptedCurp ? " - CURP: " . htmlspecialchars($decryptedCurp) : "";
        $sanitized[] = [
            "id" => $r['id'],
            "text" => htmlspecialchars($r['text']) . $extraInfo
        ];
    }

    echo json_encode(["results" => $sanitized]);

} catch (PDOException $e) {
    echo json_encode(["results" => []]);
}
