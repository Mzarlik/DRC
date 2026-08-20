<?php
// modules/peticion_rapida/export_diario_excel.php
// Genera y descarga directamente la Cédula de Reporte Diario Oficial en formato XLSX

require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Database.php';

use Core\Services\PeticionRapidaService;
use Core\Services\ExcelReportFormatter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$reporte = PeticionRapidaService::getReporteDiario($fecha);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reporte Diario ' . $fecha);
$sheet->getParent()->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

// 1. Encabezado Institucional Superior
$sheet->mergeCells('A1:D1');
$sheet->setCellValue('A1', 'DIRECCIÓN DE REGISTRO CIVIL — REPORTE DIARIO OFICIAL');
$sheet->getRowDimension(1)->setRowHeight(32);
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => ExcelReportFormatter::COLOR_HEADER_BG]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// 2. Subtítulo con Metadatos
$sheet->mergeCells('A2:D2');
$sheet->setCellValue('A2', "Fecha de Corte: {$reporte['fecha_fmt']} | Generado por: " . \Core\Auth::getUserName() . " | Fecha de Generación: " . date('d/m/Y H:i'));
$sheet->getRowDimension(2)->setRowHeight(22);
$sheet->getStyle('A2')->applyFromArray([
    'font' => [
        'italic' => true,
        'color' => ['rgb' => '374151'],
        'size' => 9.5
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F3F4F6']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// 3. Encabezados de Tabla (Fila 4)
$sheet->getRowDimension(3)->setRowHeight(10); // Espaciador
$sheet->getRowDimension(4)->setRowHeight(26);

$headers = [
    'A4' => ['#', Alignment::HORIZONTAL_CENTER],
    'B4' => ['Concepto / Trámite Oficial de Registro Civil', Alignment::HORIZONTAL_LEFT],
    'C4' => ['Código', Alignment::HORIZONTAL_CENTER],
    'D4' => ['Total del Día', Alignment::HORIZONTAL_RIGHT]
];

foreach ($headers as $cell => $info) {
    $sheet->setCellValue($cell, $info[0]);
    $sheet->getStyle($cell)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 10.5
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => ExcelReportFormatter::COLOR_HEADER_BG]
        ],
        'alignment' => [
            'horizontal' => $info[1],
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['rgb' => '3D1015']
            ]
        ]
    ]);
}

// 4. Filas de Datos
$rowNum = 5;
$i = 1;
foreach ($reporte['filas'] as $f) {
    $sheet->getRowDimension($rowNum)->setRowHeight(22);

    $sheet->setCellValue('A' . $rowNum, $i++);
    $sheet->setCellValue('B' . $rowNum, $f['nombre']);
    $sheet->setCellValue('C' . $rowNum, $f['codigo']);
    $sheet->setCellValue('D' . $rowNum, (int)$f['total']);

    // Alternancia Zebra
    if ($rowNum % 2 === 1) {
        $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => ExcelReportFormatter::COLOR_ZEBRA_ROW]
            ]
        ]);
    }

    // Alineaciones
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

    // Formato de número y estilo si tiene trámites
    if ($f['total'] > 0) {
        $sheet->getStyle('D' . $rowNum)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0F5132']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1E7DD']
            ]
        ]);
    } else {
        $sheet->getStyle('D' . $rowNum)->applyFromArray([
            'font' => [
                'color' => ['rgb' => '9CA3AF']
            ]
        ]);
    }

    // Bordes sutiles
    $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => ExcelReportFormatter::COLOR_BORDER]
            ]
        ]
    ]);

    $rowNum++;
}

// 5. Fila de Total General (Footer)
$sheet->getRowDimension($rowNum)->setRowHeight(28);
$sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
$sheet->setCellValue('A' . $rowNum, 'TOTAL GENERAL DE ACTIVIDADES DEL DÍA:');
$sheet->setCellValue('D' . $rowNum, (int)$reporte['gran_total']);

$sheet->getStyle('A' . $rowNum . ':C' . $rowNum)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 10.5
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '334155']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

$sheet->getStyle('D' . $rowNum)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => ExcelReportFormatter::COLOR_HEADER_BG]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// 6. Auto-ajuste de columnas
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(65);
$sheet->getColumnDimension('C')->setWidth(14);
$sheet->getColumnDimension('D')->setWidth(20);

// 7. Descarga directa en navegador
\Core\Auditoria::logAccion('Reportes', 'EXPORTAR', "Descarga directa de Reporte Diario Oficial XLSX. Fecha: $fecha");

$fileName = 'Reporte_Diario_Oficial_DRC_' . $fecha . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
