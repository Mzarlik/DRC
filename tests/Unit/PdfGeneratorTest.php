<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Services\PdfGenerator;

/**
 * Pruebas unitarias para el generador centralizado de Actas y Constancias en PDF.
 */
class PdfGeneratorTest extends TestCase {
    public function testGenerarActaNacimientoConUnicode() {
        $actaData = [
            'numero_acta' => 'NAC-2026-9999',
            'nombre_completo' => 'XÓCHITL TA\'AN K\'AN', // Nombres con grafías indígenas
            'curp_encrypted' => \Core\Encryption::encrypt('XOTK010101MDFRRN01'),
            'fecha_registro' => '2026-08-19',
            'lugar_nacimiento' => 'OFICIALÍA CENTRAL DEL REGISTRO CIVIL'
        ];

        $pdfBinary = PdfGenerator::generarActaNacimiento($actaData, 'http://localhost/DRC');

        $this->assertNotEmpty($pdfBinary);
        // Validar que el binario inicie con la firma de encabezado PDF (%PDF-1.)
        $this->assertStringStartsWith('%PDF-', $pdfBinary, "El archivo generado debe ser un PDF válido.");
    }

    public function testGenerarConstanciaInexistencia() {
        $constanciaData = [
            'folio' => 'INEX-2026-0088',
            'nombre_solicitante' => 'MARÍA GUADALUPE SÁNCHEZ HERNÁNDEZ',
            'tipo_acto' => 'MATRIMONIO',
            'fecha_solicitud' => '2026-08-19',
            'linea_pago' => '98765432101234567890'
        ];

        $pdfBinary = PdfGenerator::generarConstanciaInexistencia($constanciaData, 'http://localhost/DRC');

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
    }

    public function testGenerarQrFirmado() {
        $qrDataUri = PdfGenerator::generarQrFirmado('NACIMIENTO_TEST_001', 'http://localhost/DRC');
        $this->assertNotEmpty($qrDataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $qrDataUri, "El código QR generado debe ser un Data URI PNG.");
    }
}
