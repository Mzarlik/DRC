<?php
namespace Core\Services;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Servicio centralizado para el diseño, estilización y formato profesional
 * de todos los reportes y exportaciones en Excel del ERP Registro Civil.
 */
class ExcelReportFormatter {

    // Paleta Institucional
    public const COLOR_HEADER_BG    = '5C1D24'; // Guinda Oficial
    public const COLOR_HEADER_TEXT  = 'FFFFFF'; // Blanco
    public const COLOR_ZEBRA_ROW    = 'F8F9FA'; // Gris muy suave para alternancia
    public const COLOR_BORDER       = 'E5E7EB'; // Borde sutil y limpio

    // Mapeo de Colores para Estados (Soft Badge Fill + Dark Text)
    public const STATUS_STYLES = [
        // Estados Exitosos / Validados / Finalizados (Verde Suave)
        'FINALIZADO'  => ['bg' => 'D1E7DD', 'text' => '0F5132'],
        'VALIDADA'    => ['bg' => 'D1E7DD', 'text' => '0F5132'],
        'ENTREGADO'   => ['bg' => 'D1E7DD', 'text' => '0F5132'],
        'COMPLETADO'  => ['bg' => 'D1E7DD', 'text' => '0F5132'],
        'ACTIVO'      => ['bg' => 'D1E7DD', 'text' => '0F5132'],
        'CERRADA'     => ['bg' => 'D1E7DD', 'text' => '0F5132'],
        'EXITO'       => ['bg' => 'D1E7DD', 'text' => '0F5132'],

        // Estados en Progreso / Pendientes (Ámbar / Amarillo Suave)
        'PENDIENTE'   => ['bg' => 'FFF3CD', 'text' => '664D03'],
        'EN_PROCESO'  => ['bg' => 'FFF3CD', 'text' => '664D03'],
        'EN_PROGRESO' => ['bg' => 'FFF3CD', 'text' => '664D03'],
        'EN_ESPERA'   => ['bg' => 'FFF3CD', 'text' => '664D03'],
        'ABIERTA'     => ['bg' => 'FFF3CD', 'text' => '664D03'],

        // Estados Cancelados / Rechazados / Finados (Rojo Suave)
        'CANCELADO'   => ['bg' => 'F8D7DA', 'text' => '842029'],
        'RECHAZADO'   => ['bg' => 'F8D7DA', 'text' => '842029'],
        'INACTIVO'    => ['bg' => 'F8D7DA', 'text' => '842029'],
        'FINADO'      => ['bg' => 'F8D7DA', 'text' => '842029'],
        'ERROR'       => ['bg' => 'F8D7DA', 'text' => '842029'],

        // Estados de Atención Activa / Vivos (Azul Suave)
        'ATENDIENDO'  => ['bg' => 'CFE2FF', 'text' => '084298'],
        'VIVO'        => ['bg' => 'CFE2FF', 'text' => '084298']
    ];

    /**
     * Aplica el formato y diseño institucional completo a la hoja de cálculo.
     *
     * @param Worksheet $sheet Hoja de PhpSpreadsheet activa
     * @param int $totalRows Número total de filas con datos (incluye el header)
     * @param string $lastCol Letra de la última columna (ej. 'H', 'J', 'M')
     * @param array $colAlignments Array asociativo de alineación por columna (ej. ['A' => 'center', 'B' => 'left'])
     * @param string|null $statusCol Letra de la columna que contiene el estatus (ej. 'G')
     */
    public static function aplicarFormatoInstitucional(
        Worksheet $sheet,
        int $totalRows,
        string $lastCol,
        array $colAlignments = [],
        ?string $statusCol = null
    ): void {
        // 1. Tipografía Global
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

        // 2. Estilo de Fila de Encabezados (Fila 1)
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => self::COLOR_HEADER_TEXT],
                'size'  => 10.5
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::COLOR_HEADER_BG]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '3D1015']
                ]
            ]
        ]);

        // Centrar encabezados específicos si están configurados como center
        foreach ($colAlignments as $col => $align) {
            if ($align === 'center') {
                $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // 3. Estilo de Filas de Datos
        if ($totalRows >= 2) {
            $dataRange = 'A2:' . $lastCol . $totalRows;

            // Bordes ligeros para todas las celdas
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => self::COLOR_BORDER]
                    ]
                ]
            ]);

            for ($r = 2; $r <= $totalRows; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(22);

                // Alternancia de color (Zebra Striping)
                if ($r % 2 === 1) {
                    $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => self::COLOR_ZEBRA_ROW]
                        ]
                    ]);
                }

                // Aplicar alineaciones horizontales
                foreach ($colAlignments as $col => $align) {
                    $cellAlign = ($align === 'center') ? Alignment::HORIZONTAL_CENTER : 
                                 (($align === 'right') ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT);
                    
                    $sheet->getStyle($col . $r)->applyFromArray([
                        'alignment' => [
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'horizontal' => $cellAlign
                        ]
                    ]);
                }

                // Estilizar Celda de Estatus / Estado
                if ($statusCol !== null) {
                    $statusCell = $statusCol . $r;
                    $statusVal = strtoupper(trim((string)$sheet->getCell($statusCell)->getValue()));
                    self::estilizarCeldaEstado($sheet, $statusCell, $statusVal);
                }
            }
        }

        // 4. Ajuste Inteligente de Columnas (Auto-dimensionado con margen)
        $cols = range('A', $lastCol);
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Aplica estilo tipo Badge / Insignia ejecutiva a una celda de estado.
     */
    public static function estilizarCeldaEstado(Worksheet $sheet, string $cellCoordinate, string $statusValue): void {
        $statusKey = str_replace(' ', '_', $statusValue);
        $style = self::STATUS_STYLES[$statusKey] ?? null;

        if ($style) {
            $sheet->getStyle($cellCoordinate)->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => $style['text']]
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $style['bg']]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER
                ]
            ]);
        } else {
            $sheet->getStyle($cellCoordinate)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER
                ]
            ]);
        }
    }

    /**
     * Mapea claves internas a los nombres oficiales legibles de las constancias.
     */
    public static function formatearNombreConstancia(?string $rawTipo): string {
        $raw = trim($rawTipo ?? '');
        $map = [
            'INEXISTENCIA_DESCENDENCIA' => 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA',
            'CONSTANCIA_DESCENDENCIA'   => 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA',
            'NO_DEUDOR'                  => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO',
            'INEXISTENCIA_DEUDOR'       => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO',
            'INEXISTENCIA_MATRIMONIO'   => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO',
            'INEXISTENCIA_NACIMIENTO'   => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO'
        ];

        return $map[$raw] ?? $raw;
    }
}
