<?php
// core/Worker.php
// CLI Worker to process background jobs for report exporting

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Audit.php';

use Core\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

try {
    $pdo = Database::getConnection();
    
    // 1. Obtener trabajos pendientes
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE status = 'pending' ORDER BY id ASC LIMIT 5");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($jobs)) {
        echo "No hay trabajos pendientes para procesar.\n";
        exit;
    }
    
    // Crear el directorio de exportaciones si no existe
    $exportDir = __DIR__ . '/../public/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    foreach ($jobs as $job) {
        $jobId = $job['id'];
        echo "Procesando Job ID: $jobId (Tipo: {$job['type']})\n";
        
        // Actualizar estatus a 'processing'
        $stmtUpdate = $pdo->prepare("UPDATE jobs SET status = 'processing', updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$jobId]);
        
        try {
            $payload = json_decode($job['payload'], true);
            $filePath = '';
            
            if ($job['type'] === 'export_inexistencias') {
                $filePath = generateInexistenciasReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_general_report') {
                $filePath = generateGeneralReport($pdo, $payload, $jobId, $exportDir);
            } else {
                throw new Exception("Tipo de trabajo desconocido: " . $job['type']);
            }
            
            // Actualizar a completed
            $stmtComplete = $pdo->prepare("UPDATE jobs SET status = 'completed', file_path = ?, updated_at = NOW() WHERE id = ?");
            $stmtComplete->execute([$filePath, $jobId]);
            
            // Spoof session variables for Audit logger
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $job['user_id'];
            $_SERVER['REMOTE_ADDR'] = 'CLI-Worker';
            
            \Core\Audit::log('EXPORT', 'Reportes', 'Generación de reporte Excel asíncrono completado. Job ID: ' . $jobId);
            echo "Job ID: $jobId completado exitosamente.\n";
            
        } catch (Exception $e) {
            echo "Error procesando Job ID $jobId: " . $e->getMessage() . "\n";
            // Actualizar a failed
            $stmtFail = $pdo->prepare("UPDATE jobs SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ?");
            $stmtFail->execute([$e->getMessage(), $jobId]);
            
            // Log in audit
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $job['user_id'];
            $_SERVER['REMOTE_ADDR'] = 'CLI-Worker';
            \Core\Audit::log('EXPORT_ERROR', 'Reportes', 'Fallo al generar reporte asíncrono. Job ID: ' . $jobId . '. Error: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    echo "Error general en el Worker: " . $e->getMessage() . "\n";
}

/**
 * Genera el reporte Excel de inexistencias
 */
function generateInexistenciasReport($pdo, $payload, $jobId, $exportDir) {
    $tipo = $payload['tipo'] ?? '';
    
    $sql = "SELECT id, tipo_constancia, linea_pago, nombre_completo, fecha_tramite, fecha_llegada, estatus, observaciones FROM inexistencias";
    $params = [];
    if (!empty($tipo)) {
        $sql .= " WHERE tipo_constancia = ?";
        $params[] = $tipo;
    }
    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Inexistencias');
    
    // Headers
    $headers = ['ID', 'Tipo Constancia', 'Línea de Pago', 'Nombre Completo', 'Fecha Trámite', 'Fecha Llegada', 'Estatus', 'Observaciones'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFE9ECEF');
        $col++;
    }
    
    // Data
    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['id']);
        $sheet->setCellValue('B' . $rowNum, $row['tipo_constancia']);
        
        // Evita corrupción de números largos (formatea como string explícito)
        $sheet->setCellValueExplicit('C' . $rowNum, $row['linea_pago'], DataType::TYPE_STRING);
        
        $sheet->setCellValue('D' . $rowNum, $row['nombre_completo']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_tramite']);
        $sheet->setCellValue('F' . $rowNum, $row['fecha_llegada']);
        $sheet->setCellValue('G' . $rowNum, $row['estatus']);
        $sheet->setCellValue('H' . $rowNum, $row['observaciones']);
        $rowNum++;
    }
    
    // Autoajustar
    foreach (range('A', 'H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    $fileName = 'Reporte_Inexistencias_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    
    // Retornamos la ruta web-relativa para descargar
    return 'public/exports/' . $fileName;
}

/**
 * Genera el reporte general cruzado con filtros apilables
 */
function generateGeneralReport($pdo, $payload, $jobId, $exportDir) {
    $selected_modulo = $payload['modulo'] ?? '';
    $fecha_inicio = $payload['fecha_inicio'] ?? '';
    $fecha_fin = $payload['fecha_fin'] ?? '';
    $estatus = $payload['estatus'] ?? '';
    $operador_id = $payload['operador_id'] ?? '';
    
    // Estructurar mapa de modulos
    $modules_map = [
        'inexistencias' => [
            'table' => 'inexistencias',
            'modulo_label' => 'Inexistencia',
            'folio_col' => 'linea_pago',
            'ref_col' => 'nombre_completo',
            'fecha_col' => 'fecha_tramite',
            'user_col' => 'usuario_registro',
            'status_col' => 'estatus',
            'joins' => ''
        ],
        'nacimientos' => [
            'table' => 'nacimientos n',
            'modulo_label' => 'Nacimiento',
            'folio_col' => 'n.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'n.fecha_registro',
            'user_col' => 'n.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c ON n.ciudadano_id = c.id'
        ],
        'defunciones' => [
            'table' => 'defunciones d',
            'modulo_label' => 'Defunción',
            'folio_col' => 'd.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'd.fecha_registro',
            'user_col' => 'd.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c ON d.ciudadano_id = c.id'
        ],
        'foraneas' => [
            'table' => 'foraneas f',
            'modulo_label' => 'Foránea',
            'folio_col' => 'f.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'f.fecha_recepcion',
            'user_col' => 'f.usuario_registro',
            'status_col' => 'f.estatus',
            'joins' => 'JOIN ciudadanos c ON f.ciudadano_id = c.id'
        ],
        'peticiones' => [
            'table' => 'peticiones p',
            'modulo_label' => 'Petición / Ticket',
            'folio_col' => 'p.folio',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'DATE(p.fecha_creacion)',
            'user_col' => 'p.usuario_asignado',
            'status_col' => 'p.estatus',
            'joins' => 'JOIN ciudadanos c ON p.ciudadano_id = c.id'
        ],
        'matrimonios' => [
            'table' => 'matrimonios m',
            'modulo_label' => 'Matrimonio',
            'folio_col' => 'm.numero_acta',
            'ref_col' => "CONCAT(c1.nombre, ' & ', c2.nombre)",
            'fecha_col' => 'm.fecha_registro',
            'user_col' => 'm.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id'
        ],
        'divorcios' => [
            'table' => 'divorcios dv',
            'modulo_label' => 'Divorcio',
            'folio_col' => 'dv.numero_acta',
            'ref_col' => "CONCAT(c1.nombre, ' & ', c2.nombre)",
            'fecha_col' => 'dv.fecha_registro',
            'user_col' => 'dv.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c1 ON dv.ciudadano_1_id = c1.id JOIN ciudadanos c2 ON dv.ciudadano_2_id = c2.id'
        ],
        'reconocimientos' => [
            'table' => 'reconocimientos r',
            'modulo_label' => 'Reconocimiento',
            'folio_col' => 'r.numero_acta',
            'ref_col' => "CONCAT(c1.nombre, ' por ', c2.nombre)",
            'fecha_col' => 'r.fecha_registro',
            'user_col' => 'r.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c1 ON r.reconocido_id = c1.id JOIN ciudadanos c2 ON r.reconocedor_id = c2.id'
        ],
        'inscripciones' => [
            'table' => 'inscripciones i',
            'modulo_label' => 'Inscripción',
            'folio_col' => 'i.numero_acta',
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'i.fecha_registro',
            'user_col' => 'i.usuario_registro',
            'status_col' => "'FINALIZADO'",
            'joins' => 'JOIN ciudadanos c ON i.ciudadano_id = c.id'
        ],
        'tramites_curp' => [
            'table' => 'tramites_curp tc',
            'modulo_label' => 'Trámites CURP',
            'folio_col' => "CONCAT('CURP-', tc.id)",
            'ref_col' => "CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, ''))",
            'fecha_col' => 'tc.fecha_registro',
            'user_col' => 'tc.usuario_registro',
            'status_col' => 'tc.estatus',
            'joins' => 'JOIN ciudadanos c ON tc.ciudadano_id = c.id'
        ]
    ];
    
    $targets = [];
    if (!empty($selected_modulo) && isset($modules_map[$selected_modulo])) {
        $targets[$selected_modulo] = $modules_map[$selected_modulo];
    } else {
        $targets = $modules_map;
    }
    
    $sql_parts = [];
    $params = [];
    
    foreach ($targets as $key => $conf) {
        $select_fields = "'{$conf['modulo_label']}' AS modulo, {$conf['folio_col']} AS folio, {$conf['ref_col']} AS referencia, {$conf['fecha_col']} AS fecha, u.nombre AS operador, {$conf['status_col']} AS estatus";
        
        $where_clauses = ["1=1"];
        
        if (!empty($fecha_inicio)) {
            $where_clauses[] = "{$conf['fecha_col']} >= ?";
            $params[] = $fecha_inicio;
        }
        if (!empty($fecha_fin)) {
            $where_clauses[] = "{$conf['fecha_col']} <= ?";
            $params[] = $fecha_fin;
        }
        if (!empty($operador_id)) {
            $where_clauses[] = "{$conf['user_col']} = ?";
            $params[] = $operador_id;
        }
        if (!empty($estatus)) {
            $where_clauses[] = "{$conf['status_col']} = ?";
            $params[] = $estatus;
        }
        
        $where_str = implode(" AND ", $where_clauses);
        
        // Left join to user table to display name
        $sql_parts[] = "SELECT {$select_fields} FROM {$conf['table']} {$conf['joins']} LEFT JOIN usuarios u ON {$conf['user_col']} = u.id WHERE {$where_str}";
    }
    
    $full_sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY fecha DESC";
    
    $stmt = $pdo->prepare($full_sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte General');
    
    // Headers
    $headers = ['Módulo', 'ID / Folio / Acta', 'Referencia / Ciudadano', 'Fecha Registro', 'Operador / Capturista', 'Estatus'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFE9ECEF');
        $col++;
    }
    
    // Data
    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['modulo']);
        
        // Formatear folio como string estrictamente para evitar que Excel corrompa números
        $sheet->setCellValueExplicit('B' . $rowNum, $row['folio'], DataType::TYPE_STRING);
        
        $sheet->setCellValue('C' . $rowNum, $row['referencia']);
        $sheet->setCellValue('D' . $rowNum, $row['fecha']);
        $sheet->setCellValue('E' . $rowNum, $row['operador'] ?? 'N/A');
        $sheet->setCellValue('F' . $rowNum, $row['estatus']);
        $rowNum++;
    }
    
    // Autoajustar
    foreach (range('A', 'F') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    $fileName = 'Reporte_General_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    
    return 'public/exports/' . $fileName;
}
