<?php
// modules/peticion_rapida/modulo_peticiones_data.php
// Endpoint AJAX server-side para alimentar la pestaña de Peticiones de Ventanilla en cada módulo

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::check();

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Services/PeticionRapidaService.php';

use Core\Database;
use Core\Encryption;
use Core\Services\PeticionRapidaService;

try {
    $modulo = trim($_GET['modulo'] ?? '');
    $tramites = PeticionRapidaService::getTramitesPorModulo($modulo);

    $draw = intval($_GET['draw'] ?? 0);

    if (empty($tramites)) {
        echo json_encode([
            'draw' => $draw,
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'aaData' => []
        ]);
        exit;
    }

    $pdo = Database::getReadConnection();
    $start = max(0, intval($_GET['start'] ?? 0));
    $length = max(10, min(100, intval($_GET['length'] ?? 10)));
    $search = trim($_GET['search']['value'] ?? '');
    $filtroEstatus = trim($_GET['estatus'] ?? '');

    // Detectar si ya existe la columna de blind index (migración opcional)
    $hasBindex = false;
    try {
        $pdo->query("SELECT solicitante_curp_bindex FROM peticiones_ventanilla LIMIT 0");
        $hasBindex = true;
    } catch (\Throwable $e) {
        $hasBindex = false;
    }

    $inTramites = implode(',', array_fill(0, count($tramites), '?'));
    $whereParts = ["pv.deleted_at IS NULL", "pv.tipo_peticion IN ($inTramites)"];
    $params = $tramites;

    if ($filtroEstatus !== '' && in_array($filtroEstatus, ['PENDIENTE', 'EN_PROCESO', 'ENTREGADO', 'CANCELADO'], true)) {
        $whereParts[] = "pv.estatus = ?";
        $params[] = $filtroEstatus;
    }

    if ($search !== '') {
        // CURP completa → búsqueda exacta por blind index; texto libre → LIKE en campos no cifrados
        $curpCandidata = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $search));
        $esCurpCompleta = preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/', $curpCandidata) === 1;
        if ($hasBindex && $esCurpCompleta) {
            $whereParts[] = "pv.solicitante_curp_bindex = ?";
            $params[] = Encryption::getBlindIndex($curpCandidata);
        } else {
            $whereParts[] = "(pv.folio LIKE ? OR pv.solicitante_nombre LIKE ? OR pv.detalle LIKE ?)";
            $term = '%' . $search . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
    }

    $whereSql = "WHERE " . implode(' AND ', $whereParts);

    // Total general del módulo
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM peticiones_ventanilla WHERE deleted_at IS NULL AND tipo_peticion IN ($inTramites)");
    $stmtTotal->execute($tramites);
    $totalRecords = (int)$stmtTotal->fetchColumn();

    // Total filtrado
    $stmtFiltered = $pdo->prepare("SELECT COUNT(*) FROM peticiones_ventanilla pv $whereSql");
    $stmtFiltered->execute($params);
    $filteredRecords = (int)$stmtFiltered->fetchColumn();

    // Registros paginados
    $sql = "SELECT pv.id, pv.folio, pv.solicitante_nombre, pv.solicitante_curp, pv.solicitante_telefono,
                   pv.tipo_peticion, pv.detalle, pv.estatus, pv.creado_en
            FROM peticiones_ventanilla pv
            $whereSql
            ORDER BY pv.id DESC
            LIMIT $length OFFSET $start";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Descifrar la CURP del solicitante para su visualización autorizada
    foreach ($rows as &$row) {
        if (!empty($row['solicitante_curp'])) {
            $descifrada = Encryption::decrypt($row['solicitante_curp']);
            // Si el valor descifrado coincide con formato CURP se usa; si no (legado en claro), se conserva.
            $row['solicitante_curp'] = preg_match('/^[A-Z]{18}$/', $descifrada) ? $descifrada : $row['solicitante_curp'];
        }
    }
    unset($row);

    echo json_encode([
        'draw' => $draw,
        'iTotalRecords' => $totalRecords,
        'iTotalDisplayRecords' => $filteredRecords,
        'aaData' => $rows
    ]);

} catch (\Throwable $e) {
    error_log('modulo_peticiones_data: ' . $e->getMessage());
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 0),
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
        'aaData' => [],
        'error' => 'Error al consultar peticiones.'
    ]);
}
