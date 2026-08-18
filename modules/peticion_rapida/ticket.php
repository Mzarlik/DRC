<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

// modules/peticion_rapida/ticket.php
require_once '../../core/Database.php';
require_once '../../core/Encryption.php';
use Core\Database;

$id = intval($_GET['id'] ?? 0);
$ticket = null;

if ($id > 0) {
    try {
        $pdo = Database::getReadConnection();
        $stmt = $pdo->prepare("SELECT pv.id, pv.folio, pv.tipo_peticion, pv.detalle, pv.estatus, pv.creado_en,
                                      c.nombre, c.apellido_paterno, c.apellido_materno, c.curp
                               FROM peticiones_ventanilla pv
                               LEFT JOIN ciudadanos c ON pv.ciudadano_id = c.id
                               WHERE pv.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ticket && $ticket['curp']) {
            $ticket['curp'] = \Core\Encryption::decrypt($ticket['curp']);
        }
    } catch (\Throwable $e) {
        $ticket = null;
    }
}

$tipoLabel = [
    'ACTA_FORANEA' => 'ACTA FORÁNEA',
    'CONSTANCIA' => 'CONSTANCIA',
][$ticket['tipo_peticion'] ?? ''] ?? ($ticket['tipo_peticion'] ?? '');

$estadoLabel = [
    'PENDIENTE' => 'EN TRÁMITE',
    'ENTREGADO' => 'ENTREGADO',
    'CANCELADO' => 'CANCELADO',
][$ticket['estatus'] ?? ''] ?? '';

$estadoClass = [
    'PENDIENTE' => 'warning',
    'ENTREGADO' => 'success',
    'CANCELADO' => 'danger',
][$ticket['estatus'] ?? ''] ?? 'secondary';

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
        .ticket h1 { font-size: 22px; margin: 0 0 4px; letter-spacing: 1px; }
        .ticket h2 { font-size: 14px; margin: 0 0 14px; font-weight: normal; }
        .folio { font-size: 30px; font-weight: bold; letter-spacing: 3px; margin: 14px 0; padding: 10px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .row { text-align: left; margin: 8px 0; font-size: 13px; }
        .row .label { text-transform: uppercase; font-size: 11px; opacity: .8; }
        .badge { display: inline-block; border: 1px solid #000; padding: 2px 10px; font-size: 12px; margin-top: 6px; }
        .foot { margin-top: 16px; font-size: 11px; text-align: center; opacity: .8; }
        .btn { margin-top: 18px; display: block; width: 100%; padding: 10px; background: #111; color: #fff; border: none; font-size: 14px; cursor: pointer; }
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
            <div class="label">Ciudadano</div>
            <strong><?php echo htmlspecialchars(trim($ticket['nombre'] . ' ' . $ticket['apellido_paterno'] . ' ' . $ticket['apellido_materno'])); ?></strong>
        </div>
        <div class="row">
            <div class="label">Tipo</div>
            <?php echo htmlspecialchars($tipoLabel); ?>
        </div>
        <div class="row">
            <div class="label">Detalle</div>
            <?php echo htmlspecialchars($ticket['detalle'] ?? ''); ?>
        </div>
        <div class="row">
            <div class="label">Fecha</div>
            <?php echo htmlspecialchars($fecha); ?>
        </div>
        <span class="badge"><?php echo htmlspecialchars($estadoLabel); ?></span>
        <div class="foot">Presente este comprobante en la ventanilla.<br>ERP DRC - Puvlika</div>
        <?php else: ?>
        <p>Ticket no encontrado.</p>
        <?php endif; ?>
        <button onclick="window.print()">Imprimir</button>
    </div>
</body>
</html>
