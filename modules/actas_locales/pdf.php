<?php
// modules/actas_locales/pdf.php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_actas_locales');

require_once '../../vendor/autoload.php';
require_once '../../core/Database.php';

use Core\Database;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$tipo = mb_strtoupper($_GET['tipo'] ?? '', 'UTF-8');
$id = intval($_GET['id'] ?? 0);

if (!$tipo || !$id) {
    die("Parámetros insuficientes.");
}

try {
    $pdo = Database::getConnection();
    $stmt = null;
    
    switch ($tipo) {
        case 'NACIMIENTO':
            $stmt = $pdo->prepare("SELECT n.numero_acta, n.lugar_nacimiento, n.fecha_registro,
                                          c.nombre AS c_nombre, c.apellido_paterno AS c_app, c.apellido_materno AS c_apm, c.curp AS c_curp, c.fecha_nacimiento AS c_fnac, c.sexo AS c_sexo,
                                          p.nombre AS p_nombre, p.apellido_paterno AS p_app, p.apellido_materno AS p_apm,
                                          m.nombre AS m_nombre, m.apellido_paterno AS m_app, m.apellido_materno AS m_apm
                                   FROM nacimientos n JOIN ciudadanos c ON n.ciudadano_id = c.id
                                   LEFT JOIN ciudadanos p ON n.padre_id = p.id
                                   LEFT JOIN ciudadanos m ON n.madre_id = m.id WHERE n.id = :id");
            break;
        case 'MATRIMONIO':
            $stmt = $pdo->prepare("SELECT m.numero_acta, m.regimen_patrimonial, m.fecha_registro,
                                          c1.nombre AS c1_nombre, c1.apellido_paterno AS c1_app, c1.apellido_materno AS c1_apm,
                                          c2.nombre AS c2_nombre, c2.apellido_paterno AS c2_app, c2.apellido_materno AS c2_apm
                                   FROM matrimonios m JOIN ciudadanos c1 ON m.contrayente_1_id = c1.id
                                   JOIN ciudadanos c2 ON m.contrayente_2_id = c2.id WHERE m.id = :id");
            break;
        case 'DIVORCIO':
            $stmt = $pdo->prepare("SELECT d.numero_acta, d.tipo_divorcio, d.fecha_registro,
                                          c1.nombre AS c1_nombre, c1.apellido_paterno AS c1_app, c1.apellido_materno AS c1_apm,
                                          c2.nombre AS c2_nombre, c2.apellido_paterno AS c2_app, c2.apellido_materno AS c2_apm
                                   FROM divorcios d JOIN ciudadanos c1 ON d.ciudadano_1_id = c1.id
                                   JOIN ciudadanos c2 ON d.ciudadano_2_id = c2.id WHERE d.id = :id");
            break;
        case 'DEFUNCION':
            $stmt = $pdo->prepare("SELECT df.numero_acta, df.fecha_defuncion, df.causa_muerte, df.fecha_registro,
                                          c.nombre AS c_nombre, c.apellido_paterno AS c_app, c.apellido_materno AS c_apm
                                   FROM defunciones df JOIN ciudadanos c ON df.ciudadano_id = c.id WHERE df.id = :id");
            break;
        case 'RECONOCIMIENTO':
            $stmt = $pdo->prepare("SELECT r.numero_acta, r.fecha_registro,
                                          c1.nombre AS c1_nombre, c1.apellido_paterno AS c1_app, c1.apellido_materno AS c1_apm,
                                          c2.nombre AS c2_nombre, c2.apellido_paterno AS c2_app, c2.apellido_materno AS c2_apm
                                   FROM reconocimientos r JOIN ciudadanos c1 ON r.reconocido_id = c1.id
                                   JOIN ciudadanos c2 ON r.reconocedor_id = c2.id WHERE r.id = :id");
            break;
        default:
            die('Tipo de acta no soportado.');
    }
    
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        die('Registro no encontrado.');
    }

    foreach ($data as $k => $v) {
        if (strpos($k, 'curp') !== false) {
            $data[$k] = \Core\Encryption::decrypt($v);
        }
    }

    // 2. Generar Código QR Dinámico
    // En un entorno real, HTTP_HOST puede variar
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $token = base64_encode($tipo . '_' . $id);
    $validationUrl = "http://{$host}/DRC/public/validate.php?token={$token}";
    
    $qrOptions = new QROptions([
        'version'    => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L,
    ]);
    
    $qrcode = new QRCode($qrOptions);
    // Devuelve una cadena base64: data:image/png;base64,...
    $qrBase64 = $qrcode->render($validationUrl);
    // Extraer solo la parte codificada
    $qrData = substr($qrBase64, strpos($qrBase64, ',') + 1);

    // 3. Renderizar PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Protección del documento: Evita modificación y extracción (sin contraseña para abrir)
    $pdf->SetProtection(['modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble'], '', null, 0, null);

    $pdf->SetCreator('ERP DRC Puvlika');
    $pdf->SetAuthor('Dirección de Registro Civil');
    $pdf->SetTitle('Acta de ' . $tipo);
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    
    // Márgenes
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    
    // Título Oficial
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'ESTADOS UNIDOS MEXICANOS', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'DIRECCIÓN DE REGISTRO CIVIL', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Subtítulo
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'ACTA DE ' . mb_strtoupper($tipo, 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Datos del Acta
    $pdf->SetFont('helvetica', '', 11);
    
    $html = '<table cellpadding="5" border="1">';
    $html .= '<tr><td width="30%"><strong>Número de Acta:</strong></td><td width="70%">' . htmlspecialchars($data['numero_acta']) . '</td></tr>';
    $html .= '<tr><td><strong>Fecha de Registro:</strong></td><td>' . htmlspecialchars($data['fecha_registro']) . '</td></tr>';
    
    if ($tipo === 'NACIMIENTO') {
        $html .= '<tr><td><strong>Registrado:</strong></td><td>' . htmlspecialchars($data['c_nombre'] . ' ' . $data['c_app'] . ' ' . $data['c_apm']) . '</td></tr>';
        $html .= '<tr><td><strong>Lugar de Nac.:</strong></td><td>' . htmlspecialchars($data['lugar_nacimiento']) . '</td></tr>';
    } elseif ($tipo === 'MATRIMONIO') {
        $html .= '<tr><td><strong>Contrayente 1:</strong></td><td>' . htmlspecialchars($data['c1_nombre'] . ' ' . $data['c1_app'] . ' ' . $data['c1_apm']) . '</td></tr>';
        $html .= '<tr><td><strong>Contrayente 2:</strong></td><td>' . htmlspecialchars($data['c2_nombre'] . ' ' . $data['c2_app'] . ' ' . $data['c2_apm']) . '</td></tr>';
        $html .= '<tr><td><strong>Régimen:</strong></td><td>' . htmlspecialchars($data['regimen_patrimonial']) . '</td></tr>';
    } elseif ($tipo === 'DIVORCIO') {
        $html .= '<tr><td><strong>Divorciado 1:</strong></td><td>' . htmlspecialchars($data['c1_nombre'] . ' ' . $data['c1_app'] . ' ' . $data['c1_apm']) . '</td></tr>';
        $html .= '<tr><td><strong>Divorciado 2:</strong></td><td>' . htmlspecialchars($data['c2_nombre'] . ' ' . $data['c2_app'] . ' ' . $data['c2_apm']) . '</td></tr>';
    } elseif ($tipo === 'DEFUNCION') {
        $html .= '<tr><td><strong>Finado:</strong></td><td>' . htmlspecialchars($data['c_nombre'] . ' ' . $data['c_app'] . ' ' . $data['c_apm']) . '</td></tr>';
        $html .= '<tr><td><strong>Causa:</strong></td><td>' . htmlspecialchars($data['causa_muerte']) . '</td></tr>';
    } elseif ($tipo === 'RECONOCIMIENTO') {
        $html .= '<tr><td><strong>Reconocido:</strong></td><td>' . htmlspecialchars($data['c1_nombre'] . ' ' . $data['c1_app'] . ' ' . $data['c1_apm']) . '</td></tr>';
        $html .= '<tr><td><strong>Reconocedor:</strong></td><td>' . htmlspecialchars($data['c2_nombre'] . ' ' . $data['c2_app'] . ' ' . $data['c2_apm']) . '</td></tr>';
    }
    
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Ln(20);
    $pdf->Cell(0, 10, 'Firma Oficial', 0, 1, 'C');
    $pdf->Line(80, $pdf->GetY(), 130, $pdf->GetY());
    
    // Incrustar QR Code en la esquina inferior izquierda
    // Image(filename o '@' string_data, x, y, w, h)
    $pdf->Image('@' . base64_decode($qrData), 20, 230, 40, 40, 'PNG');
    
    $pdf->SetXY(65, 240);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->MultiCell(100, 5, "Valide la autenticidad de este documento oficial escaneando el código QR.\n\nEste documento no es modificable. Puvlika - ERP DRC.", 0, 'L');
    
    // Salida del PDF al navegador
    $pdf->Output('Acta_' . $tipo . '_' . $data['numero_acta'] . '.pdf', 'I');

} catch (Exception $e) {
    die('Error al generar el documento: ' . $e->getMessage());
}
