<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Services\FirmaElectronicaService;

/**
 * Pruebas unitarias para el servicio de Firma Electrónica Avanzada (PKI / X.509).
 */
class FirmaElectronicaTest extends TestCase {
    public function testFirmarYVerificarDocumento() {
        // 1. Generar par de llaves RSA de prueba
        $keyPair = FirmaElectronicaService::generarParLlavesPrueba(2048);
        $this->assertNotEmpty($keyPair['private']);
        $this->assertNotEmpty($keyPair['public']);

        // 2. Cadena original de un acta
        $cadenaOriginal = "||1.0|NACIMIENTO|2026-0001|2026-08-19|OFICIALIA 01|JUAN PEREZ||";

        // 3. Firmar documento
        $sello = FirmaElectronicaService::firmarCadena($cadenaOriginal, $keyPair['private']);
        $this->assertNotEmpty($sello);

        // 4. Verificar firma válida
        $valido = FirmaElectronicaService::verificarFirma($cadenaOriginal, $sello, $keyPair['public']);
        $this->assertTrue($valido, "La firma generada con la llave privada debe ser válida con la llave pública.");

        // 5. Verificar que alteración de datos invalide la firma
        $cadenaAlterada = "||1.0|NACIMIENTO|2026-0001|2026-08-19|OFICIALIA 01|JUAN PEREZ ALTERADO||";
        $invalido = FirmaElectronicaService::verificarFirma($cadenaAlterada, $sello, $keyPair['public']);
        $this->assertFalse($invalido, "Cualquier alteración en la cadena original debe invalidar el sello.");
    }
}
