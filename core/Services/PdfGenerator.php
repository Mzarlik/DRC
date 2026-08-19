<?php
namespace Core\Services;

use Core\Encryption;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

/**
 * Servicio Centralizado para la Generación de Actas y Constancias Oficiales en PDF.
 * Implementa fuentes Unicode completas (DejaVu Sans) para soporte de grafías indígenas
 * y sellado digital con código QR firmado mediante HMAC-SHA256.
 */
class PdfGenerator {

    /**
     * Instancia y configura el motor de PDF con metadatos y fuente Unicode DejaVu Sans.
     *
     * @param string $titulo Título oficial del documento
     * @return \TCPDF
     */
    private static function createPdfInstance(string $titulo): \TCPDF {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Metadatos institucionales
        $pdf->SetCreator('ERP Dirección de Registro Civil');
        $pdf->SetAuthor('Dirección General del Registro Civil');
        $pdf->SetTitle($titulo);
        $pdf->SetSubject('Documento Oficial de Estado Civil');

        // Configuración visual
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // FUENTE UNICODE OBLIGATORIA: dejavusans soporta el conjunto completo de glifos UTF-8
        $pdf->SetFont('dejavusans', '', 10);

        return $pdf;
    }

    /**
     * Genera un código QR firmado con HMAC-SHA256 como imagen Base64 para incrustar en el acta.
     *
     * @param string $payload Identificador del acto
     * @param string $appUrl URL base del sistema
     * @return string Data URI de la imagen PNG
     */
    public static function generarQrFirmado(string $payload, string $appUrl = ''): string {
        $firma = Encryption::sign($payload);
        $baseUrl = !empty($appUrl) ? rtrim($appUrl, '/') : 'http://localhost/DRC';
        $validateUrl = $baseUrl . '/validate.php?t=' . urlencode($firma) . '&p=' . urlencode($payload);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale'           => 4,
            'imageBase64'     => true
        ]);

        return (new QRCode($options))->render($validateUrl);
    }

    /**
     * Genera un Acta de Nacimiento Oficial en formato PDF.
     *
     * @param array $acta Datos del acta y del registrado
     * @param string $appUrl URL del sistema
     * @return string Contenido binario del PDF
     */
    public static function generarActaNacimiento(array $acta, string $appUrl = ''): string {
        $pdf = self::createPdfInstance('Acta de Nacimiento - ' . ($acta['numero_acta'] ?? 'S/N'));
        $pdf->AddPage();

        $curp = Encryption::decrypt($acta['curp_encrypted'] ?? $acta['curp'] ?? '') ?? 'S/C';
        $nombre = $acta['nombre_completo'] ?? ($acta['nombre'] . ' ' . $acta['apellido_paterno'] . ' ' . ($acta['apellido_materno'] ?? ''));
        $numeroActa = $acta['numero_acta'] ?? 'NAC-2026-0001';
        $fechaRegistro = $acta['fecha_registro'] ?? date('Y-m-d');
        $lugarNacimiento = $acta['lugar_nacimiento'] ?? 'OFICIALÍA CENTRAL';

        $payload = 'NACIMIENTO_' . $numeroActa . '_' . $fechaRegistro;
        $qrDataUri = self::generarQrFirmado($payload, $appUrl);
        $firma = Encryption::sign($payload);

        // Encabezado Institucional Guinda
        $html = '
        <style>
            .header-table { width: 100%; border-bottom: 2px solid #6b1d2f; padding-bottom: 10px; margin-bottom: 20px; }
            .title { color: #6b1d2f; font-size: 16pt; font-weight: bold; text-align: center; }
            .subtitle { color: #555; font-size: 10pt; text-align: center; }
            .section-title { background-color: #f4f6f9; color: #6b1d2f; font-weight: bold; padding: 6px; font-size: 11pt; border-left: 4px solid #6b1d2f; }
            .data-table { width: 100%; margin-top: 10px; line-height: 1.6; }
            .label { font-weight: bold; color: #333; width: 30%; }
            .value { color: #111; width: 70%; }
            .footer-box { margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 10px; }
        </style>

        <table class="header-table">
            <tr>
                <td class="title">DIRECCIÓN GENERAL DEL REGISTRO CIVIL</td>
            </tr>
            <tr>
                <td class="subtitle">ESTADO DE MÉXICO · OFICIALÍA DE ESTADO CIVIL</td>
            </tr>
            <tr>
                <td style="text-align: center; font-weight: bold; color: #8c1d33; font-size: 12pt; padding-top: 5px;">
                    COPIA CERTIFICADA DE ACTA DE NACIMIENTO
                </td>
            </tr>
        </table>

        <div class="section-title">DATOS DEL REGISTRADO</div>
        <table class="data-table">
            <tr>
                <td class="label">NÚMERO DE ACTA:</td>
                <td class="value"><strong>' . htmlspecialchars($numeroActa, ENT_QUOTES, 'UTF-8') . '</strong></td>
            </tr>
            <tr>
                <td class="label">NOMBRE COMPLETO:</td>
                <td class="value"><strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong></td>
            </tr>
            <tr>
                <td class="label">CURP:</td>
                <td class="value">' . htmlspecialchars($curp, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="label">FECHA DE REGISTRO:</td>
                <td class="value">' . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="label">LUGAR DE NACIMIENTO:</td>
                <td class="value">' . htmlspecialchars($lugarNacimiento, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <br><br>
        <div class="section-title">SELLADO DIGITAL Y VALIDACIÓN OFICIAL</div>
        <table style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="width: 25%; text-align: center;">
                    <img src="' . $qrDataUri . '" width="110" height="110" />
                </td>
                <td style="width: 75%; font-size: 8pt; color: #444; vertical-align: middle; line-height: 1.4;">
                    <strong>SELLO DIGITAL DE AUTENTICIDAD (HMAC-SHA256):</strong><br>
                    <code>' . $firma . '</code><br><br>
                    <em>El presente documento cuenta con validez legal plena. Para verificar la autenticidad de esta acta, escanee el código QR o consulte el portal oficial ingresando el folio del acta.</em>
                </td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf->Output('', 'S');
    }

    /**
     * Genera una Constancia de Inexistencia Oficial en formato PDF.
     */
    public static function generarConstanciaInexistencia(array $constancia, string $appUrl = ''): string {
        $pdf = self::createPdfInstance('Constancia de Inexistencia - ' . ($constancia['folio'] ?? 'S/F'));
        $pdf->AddPage();

        $folio = $constancia['folio'] ?? 'INEX-2026-0001';
        $solicitante = $constancia['nombre_solicitante'] ?? 'CIUDADANO SOLICITANTE';
        $tipoActo = $constancia['tipo_acto'] ?? 'NACIMIENTO';
        $fechaSolicitud = $constancia['fecha_solicitud'] ?? date('Y-m-d');
        $lineaPago = $constancia['linea_pago'] ?? 'S/L';

        $payload = 'INEXISTENCIA_' . $folio . '_' . $fechaSolicitud;
        $qrDataUri = self::generarQrFirmado($payload, $appUrl);
        $firma = Encryption::sign($payload);

        $html = '
        <style>
            .header-table { width: 100%; border-bottom: 2px solid #6b1d2f; padding-bottom: 10px; margin-bottom: 20px; }
            .title { color: #6b1d2f; font-size: 15pt; font-weight: bold; text-align: center; }
            .subtitle { color: #555; font-size: 10pt; text-align: center; }
            .section-title { background-color: #f4f6f9; color: #6b1d2f; font-weight: bold; padding: 6px; font-size: 11pt; border-left: 4px solid #6b1d2f; }
            .data-table { width: 100%; margin-top: 10px; line-height: 1.6; }
            .label { font-weight: bold; color: #333; width: 35%; }
            .value { color: #111; width: 65%; }
        </style>

        <table class="header-table">
            <tr>
                <td class="title">DIRECCIÓN GENERAL DEL REGISTRO CIVIL</td>
            </tr>
            <tr>
                <td class="subtitle">DEPARTAMENTO DE ARCHIVO CENTRAL E INEXISTENCIAS</td>
            </tr>
            <tr>
                <td style="text-align: center; font-weight: bold; color: #8c1d33; font-size: 12pt; padding-top: 5px;">
                    CONSTANCIA OFICIAL DE INEXISTENCIA DE ' . htmlspecialchars($tipoActo, ENT_QUOTES, 'UTF-8') . '
                </td>
            </tr>
        </table>

        <div class="section-title">DATOS DE LA SOLICITUD</div>
        <table class="data-table">
            <tr>
                <td class="label">FOLIO DE TRÁMITE:</td>
                <td class="value"><strong>' . htmlspecialchars($folio, ENT_QUOTES, 'UTF-8') . '</strong></td>
            </tr>
            <tr>
                <td class="label">SOLICITANTE:</td>
                <td class="value"><strong>' . htmlspecialchars($solicitante, ENT_QUOTES, 'UTF-8') . '</strong></td>
            </tr>
            <tr>
                <td class="label">LÍNEA DE PAGO BANCARIA:</td>
                <td class="value">' . htmlspecialchars($lineaPago, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="label">FECHA DE EXPEDICIÓN:</td>
                <td class="value">' . htmlspecialchars($fechaSolicitud, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <br><br>
        <p style="text-align: justify; font-size: 10pt; line-height: 1.6;">
            HACE CONSTAR: Que una vez realizada la búsqueda exhaustiva en los libros y registros electrónicos que obran en los archivos de esta Dirección General, <strong>NO SE ENCONTRÓ ASENTAMIENTO NI REGISTRO ALGUNO</strong> relativo al acto solicitado para el ciudadano en mención.
        </p>

        <br>
        <table style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="width: 25%; text-align: center;">
                    <img src="' . $qrDataUri . '" width="110" height="110" />
                </td>
                <td style="width: 75%; font-size: 8pt; color: #444; vertical-align: middle; line-height: 1.4;">
                    <strong>SELLO DIGITAL INSTITUCIONAL:</strong><br>
                    <code>' . $firma . '</code>
                </td>
            </tr>
        </table>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf->Output('', 'S');
    }
}
