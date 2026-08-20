<?php
// modules/peticion_rapida/modulo_peticiones_data.php
// Endpoint AJAX server-side para alimentar la pestaña de Peticiones de Ventanilla en cada módulo

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::check();

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Services/PeticionRapidaService.php';

use Core\Database;
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

    $inTramites = implode(',', array_fill(0, count($tramites), '?'));
    $whereParts = ["pv.deleted_at IS NULL", "pv.tipo_peticion IN ($inTramites)"];
    $params = $tramites;

    if ($filtroEstatus !== '' && in_array($filtroEstatus, ['PENDIENTE', 'EN_PROCESO', 'ENTREGADO', 'CANCELADO'], true)) {
        $whereParts[] = "pv.estatus = ?";
        $params[] = $filtroEstatus;
    }

    if ($search !== '') {
        $whereParts[] = "(pv.folio LIKE ? OR pv.solicitante_nombre LIKE ? OR pv.solicitante_curp LIKE ? OR pv.detalle LIKE ?)";
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
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
