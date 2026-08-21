<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/ticket.php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Encryption.php';
use Core\Database;

$id = intval($_GET['id'] ?? 0);
$ticket = null;

if ($id > 0) {
    try {
        $pdo = Database::getReadConnection();
        $stmt = $pdo->prepare("SELECT pv.id, pv.folio, pv.tipo_peticion, pv.detalle, pv.estatus, pv.creado_en,
                                      pv.solicitante_nombre, pv.solicitante_curp, pv.solicitante_telefono
                               FROM peticiones_ventanilla pv
                               WHERE pv.id = ? AND pv.deleted_at IS NULL");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $ticket = null;
    }
}

// Descifrar CURP almacenada (retrocompatible con registros en texto plano)
if ($ticket && !empty($ticket['solicitante_curp'])) {
    $curpDescifrada = \Core\Encryption::decrypt($ticket['solicitante_curp']);
    $ticket['solicitante_curp'] = preg_match('/^[A-Z]{18}$/', $curpDescifrada) ? $curpDescifrada : $ticket['solicitante_curp'];
}

$tipoRaw = $ticket['tipo_peticion'] ?? '';
$tramiteInfo = \Core\Services\PeticionRapidaService::TRAMITES[$tipoRaw] ?? null;
$tipoLabel = $tramiteInfo ? "[{$tramiteInfo['codigo']}] {$tramiteInfo['nombre']}" : ($tipoRaw ?: 'PETICIÓN GENERAL');

$estadoLabel = [
    'PENDIENTE' => 'EN TRÁMITE',
    'EN_PROCESO' => 'EN PROCESO',
    'ENTREGADO' => 'ENTREGADO',
    'CANCELADO' => 'CANCELADO',
][$ticket['estatus'] ?? ''] ?? '';

$fecha = $ticket ? date('d/m/Y H:i', strtotime($ticket['creado_en'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket <?php echo htmlspecialchars($ticket['folio'] ?? ''); ?></title>
    <style>
        body { font-family: 'Courier New', monospace; background: #fff; color: #000; margin: 0; padding: 20px; }
        .ticket { width: 300px; margin: 0 auto; border: 2px dashed #000; padding: 20px; text-align: center; }
        .ticket h1 { font-size: 20px; margin: 0 0 4px; letter-spacing: 1px; }
        .ticket h2 { font-size: 13px; margin: 0 0 14px; font-weight: normal; }
        .folio { font-size: 26px; font-weight: bold; letter-spacing: 2px; margin: 14px 0; padding: 8px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .row { text-align: left; margin: 8px 0; font-size: 13px; }
        .row .label { text-transform: uppercase; font-size: 11px; opacity: .8; }
        .badge { display: inline-block; border: 1px solid #000; padding: 2px 10px; font-size: 12px; margin-top: 6px; }
        .foot { margin-top: 16px; font-size: 11px; text-align: center; opacity: .8; }
        .btn { margin-top: 18px; display: block; width: 100%; padding: 10px; background: #691C32; color: #fff; border: none; font-size: 14px; font-weight: bold; cursor: pointer; border-radius: 4px; }
        @media print { .btn { display: none; } }
    </style>
</head>
<body>
    <div class="ticket">
        <h1>DIRECCIÓN DE REGISTRO CIVIL</h1>
        <h2>Petición Rápida de Ventanilla</h2>
        <?php if ($ticket): ?>
        <div class="folio"><?php echo htmlspecialchars($ticket['folio']); ?></div>
        <div class="row">
            <div class="label">Solicitante</div>
            <strong><?php echo htmlspecialchars($ticket['solicitante_nombre']); ?></strong>
        </div>
        <?php if (!empty($ticket['solicitante_curp'])): ?>
        <div class="row">
            <div class="label">CURP</div>
            <span><?php echo htmlspecialchars($ticket['solicitante_curp']); ?></span>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="label">Tipo de Trámite</div>
            <strong><?php echo htmlspecialchars($tipoLabel); ?></strong>
        </div>
        <div class="row">
            <div class="label">Detalle / Referencia</div>
            <?php echo htmlspecialchars($ticket['detalle'] ?? ''); ?>
        </div>
        <div class="row">
            <div class="label">Fecha / Hora</div>
            <?php echo htmlspecialchars($fecha); ?>
        </div>
        <span class="badge"><?php echo htmlspecialchars($estadoLabel); ?></span>
        <div class="foot">Presente este comprobante en la ventanilla.<br>ERP DRC — Registro Civil</div>
        <?php else: ?>
        <p>Ticket no encontrado o eliminado.</p>
        <?php endif; ?>
        <button class="btn" onclick="window.print()">Imprimir Ticket</button>
    </div>
</body>
</html>
