<?php
// core/CronReport.php
// Weekly Scheduled Report Executor (PDF builder & email sender simulation)

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.");
}

// Definir constante de fuentes de TCPDF de forma absoluta para evitar errores de directorio de trabajo
if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', __DIR__ . '/../vendor/tecnickcom/tcpdf/fonts/');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

use Core\Database;

try {
    // 1. Obtener la llave de seguridad del archivo .env
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        throw new Exception("Archivo .env no encontrado en la raíz.");
    }
    
    $env = @parse_ini_file($envPath);
    if ($env === false || !isset($env['CRON_SECRET'])) {
        throw new Exception("CRON_SECRET no está definido en el archivo .env.");
    }
    
    $cron_secret = $env['CRON_SECRET'];
    
    // 2. Extraer las estadísticas consumiendo public/api/stats.php internamente
    // Cambiamos el directorio de trabajo actual para que las inclusiones relativas dentro de stats.php funcionen
    $oldCwd = getcwd();
    chdir(__DIR__ . '/../public/api');
    
    $_GET['cron_token'] = $cron_secret;
    ob_start();
    require 'stats.php';
    $stats_json = ob_get_clean();
    
    // Restauramos el directorio de trabajo anterior
    chdir($oldCwd);
    
    $stats = json_decode($stats_json, true);
    if (empty($stats) || $stats['status'] !== 'success') {
        throw new Exception("Error al obtener estadísticas del endpoint: " . ($stats['message'] ?? 'JSON inválido'));
    }
    
    // 3. Crear el directorio de reportes PDF si no existe
    $reportsDir = __DIR__ . '/../public/reports';
    if (!is_dir($reportsDir)) {
        mkdir($reportsDir, 0755, true);
    }
    
    // Crear el directorio de logs si no existe
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    // 4. Generar el PDF mediante la librería TCPDF
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurar información del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ERP Registro Civil');
    $pdf->SetTitle('Reporte Semanal de Control Operativo');
    $pdf->SetSubject('Estadísticas y Recaudación');
    
    // Quitar cabecera y pie de página predeterminados
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Configurar márgenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Añadir página
    $pdf->AddPage();
    
    // Diseñar membrete
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'DIRECCIÓN DE REGISTRO CIVIL DEL ESTADO', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'ERP - Reporte Semanal Consolidado de Control Operativo', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Fecha de Emisión: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Dibujar línea separadora
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);
    
    // Resumen de Indicadores (KPIs)
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '1. Resumen de Indicadores Clave (KPIs)', 0, 1, 'L');
    $pdf->Ln(2);
    
    $cards = $stats['cards'];
    $recaudacionFormatted = '$' . number_format($cards['recaudacion_total'], 2, '.', ',');
    
    $htmlKpis = '
    <table border="1" cellpadding="5">
        <tr bgcolor="#E9ECEF" style="font-weight: bold;">
            <th>Indicador</th>
            <th align="right">Valor</th>
        </tr>
        <tr>
            <td>Trámites Procesados Hoy</td>
            <td align="right">' . $cards['tramites_hoy'] . '</td>
        </tr>
        <tr>
            <td>Tickets / Peticiones Pendientes</td>
            <td align="right">' . $cards['peticiones_pendientes'] . '</td>
        </tr>
        <tr>
            <td>Trámites de Inexistencia Pendientes</td>
            <td align="right">' . $cards['inexistencias_pendientes'] . '</td>
        </tr>
        <tr>
            <td>Actas Foráneas Validadas (Histórico)</td>
            <td align="right">' . $cards['foraneas_validadas'] . '</td>
        </tr>
        <tr bgcolor="#D1E7DD" style="font-weight: bold;">
            <td>Recaudación Proyectada Total</td>
            <td align="right">' . $recaudacionFormatted . '</td>
        </tr>
    </table>';
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($htmlKpis, true, false, true, false, '');
    $pdf->Ln(5);
    
    // Desglose de Carga y Recaudación por Módulo
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '2. Carga Operativa y Recaudación por Módulo', 0, 1, 'L');
    $pdf->Ln(2);
    
    $costs = [
        'Nacimientos' => 120, 'Defunciones' => 180, 'Matrimonios' => 420,
        'Divorcios' => 650, 'Reconocimientos' => 190, 'Inscripciones' => 580,
        'Inexistencias' => 220, 'Foráneas' => 290, 'Trámites CURP' => 80
    ];
    
    $htmlModules = '
    <table border="1" cellpadding="5">
        <tr bgcolor="#E9ECEF" style="font-weight: bold;">
            <th>Nombre del Módulo</th>
            <th align="right">Trámites Registrados</th>
            <th align="right">Costo Unitario</th>
            <th align="right">Recaudación Proyectada</th>
        </tr>';
        
    $labels = $stats['carga_operativa']['labels'];
    $data_counts = $stats['carga_operativa']['data'];
    
    foreach ($labels as $idx => $modName) {
        $count = $data_counts[$idx];
        $cost = $costs[$modName] ?? 0;
        $subtotal = $count * $cost;
        
        $htmlModules .= '
        <tr>
            <td>' . htmlspecialchars($modName) . '</td>
            <td align="right">' . $count . '</td>
            <td align="right">$' . number_format($cost, 2) . '</td>
            <td align="right">$' . number_format($subtotal, 2) . '</td>
        </tr>';
    }
    
    $htmlModules .= '</table>';
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($htmlModules, true, false, true, false, '');
    $pdf->Ln(5);
    
    // Tendencia de Carga de los últimos 7 días
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '3. Tendencia Diaria de Carga Operativa (Últimos 7 Días)', 0, 1, 'L');
    $pdf->Ln(2);
    
    $htmlTrend = '
    <table border="1" cellpadding="5">
        <tr bgcolor="#E9ECEF" style="font-weight: bold;">
            <th>Día</th>
            <th align="right">Volumen de Trámites</th>
        </tr>';
        
    $trendLabels = $stats['processed_by_day']['labels'];
    $trendData = $stats['processed_by_day']['data'];
    
    foreach ($trendLabels as $idx => $dayLabel) {
        $htmlTrend .= '
        <tr>
            <td>' . htmlspecialchars($dayLabel) . '</td>
            <td align="right">' . $trendData[$idx] . '</td>
        </tr>';
    }
    $htmlTrend .= '</table>';
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($htmlTrend, true, false, true, false, '');
    
    // Guardar el archivo PDF
    $pdfFileName = 'Reporte_Semanal_Consolidado_' . date('Ymd_His') . '.pdf';
    $pdfFullPath = $reportsDir . '/' . $pdfFileName;
    $pdf->Output($pdfFullPath, 'F');
    
    echo "PDF generado exitosamente en: $pdfFullPath\n";
    
    // 5. Simular el envío por correo a los directivos escribiendo el suceso en el archivo de log
    $emailLogFile = $logsDir . '/cron_email.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $emailLogEntry = "======================================================================\n";
    $emailLogEntry .= "[$timestamp] REPORT SENDER SERVICE - CRON AUTOMATION\n";
    $emailLogEntry .= "Remitente: noreply@drc.gob.mx\n";
    $emailLogEntry .= "Destinatarios: directores@drc.gob.mx, coordinacion@drc.gob.mx\n";
    $emailLogEntry .= "Asunto: Consolidado Semanal de Operaciones ERP DRC - " . date('d/m/Y') . "\n";
    $emailLogEntry .= "Adjunto: " . basename($pdfFullPath) . " (Ruta: $pdfFullPath)\n";
    $emailLogEntry .= "Cuerpo del Mensaje:\n";
    $emailLogEntry .= "  Estimada Dirección General,\n\n";
    $emailLogEntry .= "  Se comparte el reporte consolidado de operaciones del ERP Registro Civil.\n";
    $emailLogEntry .= "  - Volumen total de trámites acumulados.\n";
    $emailLogEntry .= "  - Recaudación financiera proyectada: $recaudacionFormatted\n\n";
    $emailLogEntry .= "  Este correo ha sido generado y enviado de manera automatizada.\n";
    $emailLogEntry .= "Estatus de Transmisión: Enviado exitosamente (MOCK_MAILER_SUCCESS)\n";
    $emailLogEntry .= "======================================================================\n\n";
    
    file_put_contents($emailLogFile, $emailLogEntry, FILE_APPEND);
    
    echo "Simulación de correo registrada en: $emailLogFile\n";
    echo "Cron Job ejecutado con éxito.\n";

} catch (Exception $e) {
    $logsDir = __DIR__ . '/../logs';
    if (is_dir($logsDir)) {
        file_put_contents($logsDir . '/cron_error.log', "[" . date('Y-m-d H:i:s') . "] Error en Cron Job: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    echo "Fallo en la ejecución del Cron Job: " . $e->getMessage() . "\n";
}
