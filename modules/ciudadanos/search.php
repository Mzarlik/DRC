<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
\Core\Auth::check();

// modules/ciudadanos/search.php
// Endpoint AJAX para Select2 / Tom Select con soporte de Blind Index y Auditoría LGPDPPSO
header('Content-Type: application/json; charset=utf-8');
require_once '../../core/Database.php';
require_once '../../core/Auditoria.php';
use Core\Database;
use Core\Auditoria;
use Core\Encryption;

$term = isset($_GET['q']) ? $_GET['q'] : '';
$estado_vital = isset($_GET['estado']) ? $_GET['estado'] : ''; // VIVO o FINADO opcional

if (!\Core\RateLimiter::check('ciudadanos_search', 60, 60)) {
    http_response_code(429);
    echo json_encode(['results' => [], 'data' => [], 'status' => 'error', 'message' => 'Límite de peticiones excedido. Intente de nuevo más tarde.']);
    exit;
}

try {
    $pdo = Database::getReadConnection();
    
    $params = [];
    $termClean = trim($term);
    
    // Si coincide con el formato de un CURP completo, hacer búsqueda indexada por Blind Index HMAC
    if (preg_match('/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/i', $termClean)) {
        $cleanCurp = mb_strtoupper($termClean, 'UTF-8');
        $bindex = Encryption::getBlindIndex($cleanCurp);
        $exactCurpLegacy = Encryption::encrypt($cleanCurp);

        $sql = "SELECT id, curp, curp_encrypted, curp_bindex, CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS text,
                       CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS nombre_completo 
                FROM ciudadanos 
                WHERE estado = 1 AND (curp_bindex = :bindex OR curp = :exact_curp)";
        $params[':bindex'] = $bindex;
        $params[':exact_curp'] = $exactCurpLegacy;
    } else {
        $sql = "SELECT id, curp, curp_encrypted, curp_bindex, CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS text,
                       CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) AS nombre_completo 
                FROM ciudadanos 
                WHERE estado = 1 AND (nombre LIKE :term OR apellido_paterno LIKE :term OR apellido_materno LIKE :term)";
        $params[':term'] = '%' . $termClean . '%';
    }

    if ($estado_vital !== '') {
        $sql .= " AND estado_vital = :estado";
        $params[':estado'] = $estado_vital;
    }

    $sql .= " LIMIT 30";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Sanitizar y desencriptar salida
    $sanitized = [];
    $dataArray = [];
    foreach($results as $r) {
        $rawCurp = $r['curp_encrypted'] ?? $r['curp'];
        $decryptedCurp = Encryption::decrypt($rawCurp);
        $extraInfo = $decryptedCurp ? " - CURP: " . htmlspecialchars($decryptedCurp) : "";
        
        $sanitized[] = [
            "id" => $r['id'],
            "text" => htmlspecialchars($r['text']) . $extraInfo
        ];

        $dataArray[] = [
            "id" => $r['id'],
            "nombre_completo" => htmlspecialchars($r['nombre_completo']),
            "curp" => htmlspecialchars($decryptedCurp ?? 'S/C')
        ];
    }

    // Auditoría de lectura obligatoria (LGPDPPSO / INAI)
    if (!empty($termClean)) {
        Auditoria::logLectura('Ciudadanos', 'BUSQUEDA_CIUDADANO', "Búsqueda con término: '$termClean' - Resultados encontrados: " . count($results));
    }

    echo json_encode([
        "results" => $sanitized,
        "data" => $dataArray
    ]);

} catch (PDOException $e) {
    echo json_encode(["results" => [], "data" => []]);
}
