<?php
// core/Worker.php
// CLI Worker to process background jobs for report exporting

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

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
            } elseif ($job['type'] === 'export_ciudadanos') {
                $filePath = generateCiudadanosReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_nacimientos') {
                $filePath = generateNacimientosReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_matrimonios') {
                $filePath = generateMatrimoniosReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_divorcios') {
                $filePath = generateDivorciosReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_defunciones') {
                $filePath = generateDefuncionesReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_inscripciones') {
                $filePath = generateInscripcionesReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_reconocimientos') {
                $filePath = generateReconocimientosReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_actas_locales') {
                $filePath = generateActasLocalesReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_foraneas') {
                $filePath = generateForaneasReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_usuarios') {
                $filePath = generateUsuariosReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_auditoria') {
                $filePath = generateAuditoriaReport($pdo, $payload, $jobId, $exportDir);
            } elseif ($job['type'] === 'export_errores') {
                $filePath = generateErroresReport($pdo, $payload, $jobId, $exportDir);
            } else {
                throw new Exception("Tipo de trabajo desconocido: " . $job['type']);
            }

            // Limpieza automática de reportes con antigüedad superior a 48 horas
            $files = glob($exportDir . '/*.xlsx');
            $now = time();
            $threshold = 48 * 3600;
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file)) > $threshold) {
                    @unlink($file);
                }
            }
            
            // Actualizar a completed
            $stmtComplete = $pdo->prepare("UPDATE jobs SET status = 'completed', file_path = ?, updated_at = NOW() WHERE id = ?");
            $stmtComplete->execute([$filePath, $jobId]);
            
            // Spoof session variables for Audit logger
            if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
                session_start();
            }
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            $_SESSION['user_id'] = $job['user_id'];
            $_SERVER['REMOTE_ADDR'] = 'CLI-Worker';
            
            \Core\Auditoria::logAccion('Reportes', 'EXPORTAR', 'Generación de reporte Excel asíncrono completado. Job ID: ' . $jobId);
            echo "Job ID: $jobId completado exitosamente.\n";
            
        } catch (Exception $e) {
            echo "Error procesando Job ID $jobId: " . $e->getMessage() . "\n";
            // Actualizar a failed
            $stmtFail = $pdo->prepare("UPDATE jobs SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ?");
            $stmtFail->execute([$e->getMessage(), $jobId]);
            
            // Log in audit
            if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
                session_start();
            }
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            $_SESSION['user_id'] = $job['user_id'];
            $_SERVER['REMOTE_ADDR'] = 'CLI-Worker';
            \Core\Auditoria::logAccion('Reportes', 'EXPORTAR_ERROR', 'Fallo al generar reporte asíncrono. Job ID: ' . $jobId . '. Error: ' . $e->getMessage());
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
            'table' => 'inexistencias inex',
            'modulo_label' => 'Inexistencia',
            'folio_col' => 'inex.linea_pago',
            'ref_col' => 'inex.nombre_completo',
            'fecha_col' => 'inex.fecha_tramite',
            'user_col' => 'inex.usuario_registro',
            'status_col' => 'inex.estatus',
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

/**
 * Helper to style Excel headers uniformly
 */
function styleExcelHeader($sheet, $colMax) {
    $col = 'A';
    while (true) {
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFE9ECEF');
        if ($col === $colMax) break;
        $col++;
    }
}

/**
 * Helper to auto-fit columns
 */
function autoFitColumns($sheet, $colMax) {
    $col = 'A';
    while (true) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
        if ($col === $colMax) break;
        $col++;
    }
}

function generateCiudadanosReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT curp, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, estado_vital FROM ciudadanos WHERE estado = 1";
    $params = [];
    if (!empty($search)) {
        $cleanSearch = trim($search);
        if (preg_match('/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/i', $cleanSearch)) {
            $sql .= " AND curp = ? ";
            $params[] = \Core\Encryption::encrypt(mb_strtoupper($cleanSearch, 'UTF-8'));
        } else {
            $sql .= " AND (nombre LIKE ? OR apellido_paterno LIKE ? OR apellido_materno LIKE ?) ";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
    }
    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Ciudadanos');

    $headers = ['CURP', 'Nombre', 'Apellido Paterno', 'Apellido Materno', 'Sexo', 'Fecha Nacimiento', 'Estado Vital'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'G');

    $rowNum = 2;
    foreach ($records as $row) {
        $curp = \Core\Encryption::decrypt($row['curp']) ?? '';
        $sheet->setCellValueExplicit('A' . $rowNum, $curp, DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['nombre']);
        $sheet->setCellValue('C' . $rowNum, $row['apellido_paterno']);
        $sheet->setCellValue('D' . $rowNum, $row['apellido_materno']);
        $sheet->setCellValue('E' . $rowNum, $row['sexo']);
        $sheet->setCellValue('F' . $rowNum, $row['fecha_nacimiento']);
        $sheet->setCellValue('G' . $rowNum, $row['estado_vital']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'G');

    $fileName = 'Reporte_Ciudadanos_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateNacimientosReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT n.numero_acta, n.fecha_registro, n.lugar_nacimiento, 
                   CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) AS nombre_completo,
                   CONCAT_WS(' ', cp.nombre, cp.apellido_paterno, cp.apellido_materno) AS padre,
                   CONCAT_WS(' ', cm.nombre, cm.apellido_paterno, cm.apellido_materno) AS madre
            FROM nacimientos n 
            INNER JOIN ciudadanos c ON n.ciudadano_id = c.id
            LEFT JOIN ciudadanos cp ON n.padre_id = cp.id
            LEFT JOIN ciudadanos cm ON n.madre_id = cm.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (n.numero_acta LIKE ? OR c.nombre LIKE ? OR c.apellido_paterno LIKE ? OR cp.nombre LIKE ? OR cm.nombre LIKE ?) ";
        $params = array_fill(0, 5, '%' . $search . '%');
    }
    $sql .= " ORDER BY n.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Nacimientos');

    $headers = ['No. Acta', 'Registrado', 'Padre', 'Madre', 'Lugar Nacimiento', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'F');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['nombre_completo']);
        $sheet->setCellValue('C' . $rowNum, $row['padre'] ?: 'N/A');
        $sheet->setCellValue('D' . $rowNum, $row['madre'] ?: 'N/A');
        $sheet->setCellValue('E' . $rowNum, $row['lugar_nacimiento']);
        $sheet->setCellValue('F' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'F');

    $fileName = 'Reporte_Nacimientos_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateMatrimoniosReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT m.numero_acta, m.regimen_patrimonial, m.fecha_registro, 
                   CONCAT_WS(' ', c1.nombre, c1.apellido_paterno, c1.apellido_materno) AS contrayente_1, 
                   CONCAT_WS(' ', c2.nombre, c2.apellido_paterno, c2.apellido_materno) AS contrayente_2 
            FROM matrimonios m 
            JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id 
            JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (m.numero_acta LIKE ? OR c1.nombre LIKE ? OR c1.apellido_paterno LIKE ? OR c2.nombre LIKE ? OR c2.apellido_paterno LIKE ?) ";
        $params = array_fill(0, 5, '%' . $search . '%');
    }
    $sql .= " ORDER BY m.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Matrimonios');

    $headers = ['No. Acta', 'Contrayente 1', 'Contrayente 2', 'Régimen Patrimonial', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'E');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['contrayente_1']);
        $sheet->setCellValue('C' . $rowNum, $row['contrayente_2']);
        $sheet->setCellValue('D' . $rowNum, $row['regimen_patrimonial']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'E');

    $fileName = 'Reporte_Matrimonios_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateDivorciosReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT d.numero_acta, d.tipo_divorcio, d.fecha_registro, 
                   CONCAT_WS(' ', c1.nombre, c1.apellido_paterno, c1.apellido_materno) AS divorciado_1, 
                   CONCAT_WS(' ', c2.nombre, c2.apellido_paterno, c2.apellido_materno) AS divorciado_2 
            FROM divorcios d 
            JOIN ciudadanos c1 ON d.ciudadano_1_id = c1.id 
            JOIN ciudadanos c2 ON d.ciudadano_2_id = c2.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (d.numero_acta LIKE ? OR c1.nombre LIKE ? OR c1.apellido_paterno LIKE ? OR c2.nombre LIKE ? OR c2.apellido_paterno LIKE ?) ";
        $params = array_fill(0, 5, '%' . $search . '%');
    }
    $sql .= " ORDER BY d.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Divorcios');

    $headers = ['No. Acta', 'Cónyuge 1', 'Cónyuge 2', 'Tipo Divorcio', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'E');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['divorciado_1']);
        $sheet->setCellValue('C' . $rowNum, $row['divorciado_2']);
        $sheet->setCellValue('D' . $rowNum, $row['tipo_divorcio']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'E');

    $fileName = 'Reporte_Divorcios_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateDefuncionesReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT d.numero_acta, d.fecha_defuncion, d.fecha_registro, d.causa_muerte, 
                   CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) AS nombre_completo 
            FROM defunciones d 
            INNER JOIN ciudadanos c ON d.ciudadano_id = c.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (d.numero_acta LIKE ? OR c.nombre LIKE ? OR c.apellido_paterno LIKE ? OR d.causa_muerte LIKE ?) ";
        $params = array_fill(0, 4, '%' . $search . '%');
    }
    $sql .= " ORDER BY d.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Defunciones');

    $headers = ['No. Acta', 'Finado', 'Fecha Defunción', 'Causa Muerte', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'E');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['nombre_completo']);
        $sheet->setCellValue('C' . $rowNum, $row['fecha_defuncion']);
        $sheet->setCellValue('D' . $rowNum, $row['causa_muerte']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'E');

    $fileName = 'Reporte_Defunciones_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateInscripcionesReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT i.numero_acta, i.pais_origen, i.documento_extranjero, i.fecha_registro, 
                   CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) AS ciudadano 
            FROM inscripciones i 
            JOIN ciudadanos c ON i.ciudadano_id = c.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (i.numero_acta LIKE ? OR i.pais_origen LIKE ? OR c.nombre LIKE ? OR c.apellido_paterno LIKE ?) ";
        $params = array_fill(0, 4, '%' . $search . '%');
    }
    $sql .= " ORDER BY i.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Inscripciones');

    $headers = ['No. Acta', 'Ciudadano', 'País de Origen', 'Documento Extranjero', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'E');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['ciudadano']);
        $sheet->setCellValue('C' . $rowNum, $row['pais_origen']);
        $sheet->setCellValue('D' . $rowNum, $row['documento_extranjero']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'E');

    $fileName = 'Reporte_Inscripciones_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateReconocimientosReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT r.numero_acta, r.fecha_registro, 
                   CONCAT_WS(' ', c1.nombre, c1.apellido_paterno, c1.apellido_materno) AS reconocido, 
                   CONCAT_WS(' ', c2.nombre, c2.apellido_paterno, c2.apellido_materno) AS reconocedor 
            FROM reconocimientos r 
            JOIN ciudadanos c1 ON r.reconocido_id = c1.id 
            JOIN ciudadanos c2 ON r.reconocedor_id = c2.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (r.numero_acta LIKE ? OR c1.nombre LIKE ? OR c1.apellido_paterno LIKE ? OR c2.nombre LIKE ? OR c2.apellido_paterno LIKE ?) ";
        $params = array_fill(0, 5, '%' . $search . '%');
    }
    $sql .= " ORDER BY r.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reconocimientos');

    $headers = ['No. Acta', 'Reconocido', 'Reconocedor', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'D');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['reconocido']);
        $sheet->setCellValue('C' . $rowNum, $row['reconocedor']);
        $sheet->setCellValue('D' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'D');

    $fileName = 'Reporte_Reconocimientos_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateActasLocalesReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $filter_tipo = $payload['tipo_acta'] ?? '';
    
    $subqueries = [];
    if ($filter_tipo === '' || $filter_tipo === 'NACIMIENTO') {
        $subqueries[] = "SELECT 'NACIMIENTO' AS tipo_acta, n.numero_acta, n.fecha_registro,
                                CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', IFNULL(c.apellido_materno, '')) AS ciudadano_1,
                                '' AS ciudadano_2, c.curp AS curp_1, '' AS curp_2
                         FROM nacimientos n JOIN ciudadanos c ON n.ciudadano_id = c.id";
    }
    if ($filter_tipo === '' || $filter_tipo === 'MATRIMONIO') {
        $subqueries[] = "SELECT 'MATRIMONIO' AS tipo_acta, m.numero_acta, m.fecha_registro,
                                CONCAT(c1.nombre, ' ', c1.apellido_paterno, ' ', IFNULL(c1.apellido_materno, '')) AS ciudadano_1,
                                CONCAT(c2.nombre, ' ', c2.apellido_paterno, ' ', IFNULL(c2.apellido_materno, '')) AS ciudadano_2,
                                c1.curp AS curp_1, c2.curp AS curp_2
                         FROM matrimonios m JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id";
    }
    if ($filter_tipo === '' || $filter_tipo === 'DIVORCIO') {
        $subqueries[] = "SELECT 'DIVORCIO' AS tipo_acta, d.numero_acta, d.fecha_registro,
                                CONCAT(c1.nombre, ' ', c1.apellido_paterno, ' ', IFNULL(c1.apellido_materno, '')) AS ciudadano_1,
                                CONCAT(c2.nombre, ' ', c2.apellido_paterno, ' ', IFNULL(c2.apellido_materno, '')) AS ciudadano_2,
                                c1.curp AS curp_1, c2.curp AS curp_2
                         FROM divorcios d JOIN ciudadanos c1 ON d.ciudadano_1_id = c1.id JOIN ciudadanos c2 ON d.ciudadano_2_id = c2.id";
    }
    if ($filter_tipo === '' || $filter_tipo === 'DEFUNCION') {
        $subqueries[] = "SELECT 'DEFUNCION' AS tipo_acta, df.numero_acta, df.fecha_registro,
                                CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', IFNULL(c.apellido_materno, '')) AS ciudadano_1,
                                '' AS ciudadano_2, c.curp AS curp_1, '' AS curp_2
                         FROM defunciones df JOIN ciudadanos c ON df.ciudadano_id = c.id";
    }
    if ($filter_tipo === '' || $filter_tipo === 'RECONOCIMIENTO') {
        $subqueries[] = "SELECT 'RECONOCIMIENTO' AS tipo_acta, r.numero_acta, r.fecha_registro,
                                CONCAT(c1.nombre, ' ', c1.apellido_paterno, ' ', IFNULL(c1.apellido_materno, '')) AS ciudadano_1,
                                CONCAT(c2.nombre, ' ', c2.apellido_paterno, ' ', IFNULL(c2.apellido_materno, '')) AS ciudadano_2,
                                c1.curp AS curp_1, c2.curp AS curp_2
                         FROM reconocimientos r JOIN ciudadanos c1 ON r.reconocido_id = c1.id JOIN ciudadanos c2 ON r.reconocedor_id = c2.id";
    }
    
    $unionSql = implode(' UNION ALL ', $subqueries);
    $searchQuery = "";
    $params = [];
    
    if ($search != '') {
        $isCurp = preg_match('/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/i', $search);
        if ($isCurp) {
            $encryptedCurp = \Core\Encryption::encrypt(mb_strtoupper($search, 'UTF-8'));
            $searchQuery = " WHERE (numero_acta LIKE :search1 OR ciudadano_1 LIKE :search2 OR ciudadano_2 LIKE :search3 OR curp_1 = :search4 OR curp_2 = :search5)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
            $params[':search4'] = $encryptedCurp;
            $params[':search5'] = $encryptedCurp;
        } else {
            $searchQuery = " WHERE (numero_acta LIKE :search1 OR ciudadano_1 LIKE :search2 OR ciudadano_2 LIKE :search3)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }
    }
    
    $sql = "SELECT * FROM (" . $unionSql . ") AS t" . $searchQuery . " ORDER BY t.fecha_registro DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Actas Locales');

    $headers = ['Tipo de Acta', 'No. Acta', 'Ciudadano 1', 'Ciudadano 2', 'CURP 1', 'CURP 2', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'G');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['tipo_acta']);
        $sheet->setCellValueExplicit('B' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('C' . $rowNum, $row['ciudadano_1']);
        $sheet->setCellValue('D' . $rowNum, $row['ciudadano_2'] ?: 'N/A');
        
        $curp1 = \Core\Encryption::decrypt($row['curp_1']) ?? '';
        $curp2 = \Core\Encryption::decrypt($row['curp_2']) ?? '';
        $sheet->setCellValueExplicit('E' . $rowNum, $curp1, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, $curp2, DataType::TYPE_STRING);
        
        $sheet->setCellValue('G' . $rowNum, $row['fecha_registro']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'G');

    $fileName = 'Reporte_Actas_Locales_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateForaneasReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT f.numero_acta, f.estado_origen, f.tipo_acta, f.fecha_recepcion, f.estatus, f.observaciones,
                   CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno) AS nombre_completo 
            FROM foraneas f 
            INNER JOIN ciudadanos c ON f.ciudadano_id = c.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (f.numero_acta LIKE ? OR c.nombre LIKE ? OR c.apellido_paterno LIKE ? OR f.estado_origen LIKE ?) ";
        $params = array_fill(0, 4, '%' . $search . '%');
    }
    $sql .= " ORDER BY f.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Actas Foráneas');

    $headers = ['No. Acta', 'Ciudadano', 'Estado Origen', 'Tipo Acta', 'Fecha Recepción', 'Estatus', 'Observaciones'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'G');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, $row['numero_acta'], DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, $row['nombre_completo']);
        $sheet->setCellValue('C' . $rowNum, $row['estado_origen']);
        $sheet->setCellValue('D' . $rowNum, $row['tipo_acta']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_recepcion']);
        $sheet->setCellValue('F' . $rowNum, $row['estatus']);
        $sheet->setCellValue('G' . $rowNum, $row['observaciones']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'G');

    $fileName = 'Reporte_Foraneas_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateUsuariosReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT id, nombre, correo, rol, estatus, creado_en FROM usuarios";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (nombre LIKE ? OR correo LIKE ? OR rol LIKE ?) ";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Usuarios');

    $headers = ['ID', 'Nombre', 'Correo', 'Rol', 'Estatus', 'Fecha Registro'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'F');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['id']);
        $sheet->setCellValue('B' . $rowNum, $row['nombre']);
        $sheet->setCellValue('C' . $rowNum, $row['correo']);
        $sheet->setCellValue('D' . $rowNum, $row['rol']);
        $sheet->setCellValue('E' . $rowNum, $row['estatus'] == 1 ? 'ACTIVO' : 'INACTIVO');
        $sheet->setCellValue('F' . $rowNum, $row['creado_en']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'F');

    $fileName = 'Reporte_Usuarios_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateAuditoriaReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT a.id, a.fecha_hora, u.nombre as usuario, a.modulo, a.accion, a.detalles, a.ip_address 
            FROM auditoria_logs a 
            LEFT JOIN usuarios u ON a.usuario_id = u.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (a.modulo LIKE ? OR a.accion LIKE ? OR a.detalles LIKE ? OR u.nombre LIKE ? OR a.ip_address LIKE ?) ";
        $params = array_fill(0, 5, '%' . $search . '%');
    }
    $sql .= " ORDER BY a.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Logs Auditoria');

    $headers = ['ID', 'Fecha/Hora', 'Usuario', 'Módulo', 'Acción', 'Detalles', 'IP'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'G');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['id']);
        $sheet->setCellValue('B' . $rowNum, $row['fecha_hora']);
        $sheet->setCellValue('C' . $rowNum, $row['usuario'] ?? 'Sistema');
        $sheet->setCellValue('D' . $rowNum, $row['modulo']);
        $sheet->setCellValue('E' . $rowNum, $row['accion']);
        $sheet->setCellValue('F' . $rowNum, $row['detalles']);
        $sheet->setCellValue('G' . $rowNum, $row['ip_address']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'G');

    $fileName = 'Reporte_Auditoria_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}

function generateErroresReport($pdo, $payload, $jobId, $exportDir) {
    $search = $payload['search'] ?? '';
    $sql = "SELECT e.id, e.fecha_hora, u.nombre as usuario, e.mensaje, e.archivo, e.linea, e.stack_trace, e.url, e.ip_address 
            FROM error_logs e 
            LEFT JOIN usuarios u ON e.usuario_id = u.id";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE (e.mensaje LIKE ? OR e.archivo LIKE ? OR u.nombre LIKE ? OR e.url LIKE ?) ";
        $params = array_fill(0, 4, '%' . $search . '%');
    }
    $sql .= " ORDER BY e.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Logs Errores');

    $headers = ['ID', 'Fecha/Hora', 'Usuario', 'Mensaje', 'Archivo', 'Línea', 'URL', 'IP', 'Stack Trace'];
    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '1', $header);
    }
    styleExcelHeader($sheet, 'I');

    $rowNum = 2;
    foreach ($records as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['id']);
        $sheet->setCellValue('B' . $rowNum, $row['fecha_hora']);
        $sheet->setCellValue('C' . $rowNum, $row['usuario'] ?? 'Sistema');
        $sheet->setCellValue('D' . $rowNum, $row['mensaje']);
        $sheet->setCellValue('E' . $rowNum, $row['archivo']);
        $sheet->setCellValue('F' . $rowNum, $row['linea']);
        $sheet->setCellValue('G' . $rowNum, $row['url']);
        $sheet->setCellValue('H' . $rowNum, $row['ip_address']);
        $sheet->setCellValue('I' . $rowNum, $row['stack_trace']);
        $rowNum++;
    }
    autoFitColumns($sheet, 'I');

    $fileName = 'Reporte_Errores_' . $jobId . '_' . date('Ymd_His') . '.xlsx';
    $fullPath = $exportDir . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($fullPath);
    return 'public/exports/' . $fileName;
}
