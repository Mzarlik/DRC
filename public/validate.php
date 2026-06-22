<?php
// public/validate.php
require_once '../core/Database.php';

$token = $_GET['token'] ?? '';
$isValid = false;
$actaInfo = [];

if ($token) {
    // Decodificar el token (ej. base64_encode("NACIMIENTO_12"))
    $decoded = base64_decode($token);
    $parts = explode('_', $decoded);
    
    if (count($parts) === 2) {
        $tipo = mb_strtoupper($parts[0], 'UTF-8');
        $id = intval($parts[1]);

        try {
            $pdo = \Core\Database::getConnection();
            $sql = "";
            switch ($tipo) {
                case 'NACIMIENTO':
                    $sql = "SELECT a.numero_acta, a.fecha_registro, CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) as p1 
                            FROM nacimientos a JOIN ciudadanos c ON a.ciudadano_id = c.id WHERE a.id = ?";
                    break;
                case 'MATRIMONIO':
                    $sql = "SELECT a.numero_acta, a.fecha_registro, 
                            CONCAT_WS(' ', c1.nombre, c1.apellido_paterno, c1.apellido_materno) as p1,
                            CONCAT_WS(' ', c2.nombre, c2.apellido_paterno, c2.apellido_materno) as p2
                            FROM matrimonios a 
                            JOIN ciudadanos c1 ON a.contrayente_1_id = c1.id 
                            JOIN ciudadanos c2 ON a.contrayente_2_id = c2.id WHERE a.id = ?";
                    break;
                case 'DIVORCIO':
                    $sql = "SELECT a.numero_acta, a.fecha_registro, 
                            CONCAT_WS(' ', c1.nombre, c1.apellido_paterno, c1.apellido_materno) as p1,
                            CONCAT_WS(' ', c2.nombre, c2.apellido_paterno, c2.apellido_materno) as p2
                            FROM divorcios a 
                            JOIN ciudadanos c1 ON a.ciudadano_1_id = c1.id 
                            JOIN ciudadanos c2 ON a.ciudadano_2_id = c2.id WHERE a.id = ?";
                    break;
                case 'DEFUNCION':
                    $sql = "SELECT a.numero_acta, a.fecha_registro, CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) as p1 
                            FROM defunciones a JOIN ciudadanos c ON a.ciudadano_id = c.id WHERE a.id = ?";
                    break;
                case 'RECONOCIMIENTO':
                    $sql = "SELECT a.numero_acta, a.fecha_registro, 
                            CONCAT_WS(' ', c1.nombre, c1.apellido_paterno, c1.apellido_materno) as p1,
                            CONCAT_WS(' ', c2.nombre, c2.apellido_paterno, c2.apellido_materno) as p2
                            FROM reconocimientos a 
                            JOIN ciudadanos c1 ON a.reconocido_id = c1.id 
                            JOIN ciudadanos c2 ON a.reconocedor_id = c2.id WHERE a.id = ?";
                    break;
            }

            if ($sql) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $row = $stmt->fetch();

                if ($row) {
                    $isValid = true;
                    $actaInfo = [
                        'tipo' => $tipo,
                        'numero_acta' => $row['numero_acta'],
                        'fecha_registro' => $row['fecha_registro'],
                        'involucrados' => $row['p1'] . (isset($row['p2']) ? ' y ' . $row['p2'] : '')
                    ];
                }
            }
        } catch (Exception $e) {
            // Manejar error silenciosamente
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación Oficial - Puvlika</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .validation-card { max-width: 500px; margin: 50px auto; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .validation-header { background: #1a252f; color: white; padding: 30px 20px; text-align: center; }
        .validation-body { padding: 30px; background: white; }
        .status-icon { font-size: 4rem; margin-bottom: 15px; }
        .status-valid { color: #18bc9c; }
        .status-invalid { color: #e74c3c; }
        .data-row { padding: 10px 0; border-bottom: 1px solid #eee; }
        .data-row:last-child { border-bottom: none; }
        .data-label { font-weight: 600; color: #7f8c8d; font-size: 0.9rem; margin-bottom: 2px; }
        .data-value { font-weight: 700; color: #2c3e50; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="card validation-card">
        <div class="validation-header">
            <h3 class="mb-0 fw-bold">PUVLIKA</h3>
            <p class="text-white-50 mb-0 small">Sistema de Validación Documental DRC</p>
        </div>
        <div class="validation-body text-center">
            <?php if ($isValid): ?>
                <i class="fa-solid fa-circle-check status-icon status-valid"></i>
                <h4 class="fw-bold mb-4">Documento Auténtico</h4>
                
                <div class="text-start">
                    <div class="data-row">
                        <div class="data-label">Tipo de Acta</div>
                        <div class="data-value"><?php echo htmlspecialchars($actaInfo['tipo']); ?></div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Número de Acta</div>
                        <div class="data-value"><?php echo htmlspecialchars($actaInfo['numero_acta']); ?></div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Involucrado(s)</div>
                        <div class="data-value"><?php echo htmlspecialchars($actaInfo['involucrados']); ?></div>
                    </div>
                    <div class="data-row">
                        <div class="data-label">Fecha de Registro</div>
                        <div class="data-value"><?php echo htmlspecialchars($actaInfo['fecha_registro']); ?></div>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top text-muted small">
                    <i class="fa-solid fa-shield-halved me-1"></i> Este documento electrónico ha sido verificado contra la base de datos oficial.
                </div>
            <?php else: ?>
                <i class="fa-solid fa-circle-xmark status-icon status-invalid"></i>
                <h4 class="fw-bold mb-3">Documento Inválido</h4>
                <p class="text-muted mb-0">El código QR escaneado no corresponde a ningún documento emitido oficialmente o ha sido alterado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
