<?php
// modules/inexistencias/export_excel.php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');

require_once '../../vendor/autoload.php';
require_once '../../core/Database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$tipo = $_GET['tipo'] ?? '';

try {
    $pdo = \Core\Database::getConnection();
    
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
        
        // Evita que Excel corrompa cadenas numéricas largas formateándolas estrictamente como String
        $sheet->setCellValueExplicit('C' . $rowNum, $row['linea_pago'], DataType::TYPE_STRING);
        
        $sheet->setCellValue('D' . $rowNum, $row['nombre_completo']);
        $sheet->setCellValue('E' . $rowNum, $row['fecha_tramite']);
        $sheet->setCellValue('F' . $rowNum, $row['fecha_llegada']);
        $sheet->setCellValue('G' . $rowNum, $row['estatus']);
        $sheet->setCellValue('H' . $rowNum, $row['observaciones']);
        $rowNum++;
    }

    // Autoajustar columnas
    foreach (range('A', 'H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Preparar Headers HTTP para la descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_Inexistencias_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (\Exception $e) {
    die("Error crítico al exportar el reporte: " . $e->getMessage());
}
