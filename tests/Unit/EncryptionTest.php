<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Encryption;

/**
 * Pruebas unitarias para Core\Encryption que verifican el cifrado,
 * descifrado, generación de Blind Index HMAC y firmas digitales.
 */
class EncryptionTest extends TestCase {
    /**
     * Valida que getBlindIndex sea determinista para permitir búsquedas exactas indexadas.
     */
    public function testBlindIndexDeterminista() {
        $curp = "ABCD010101HDFRRN09";
        
        $bindex1 = Encryption::getBlindIndex($curp);
        $bindex2 = Encryption::getBlindIndex($curp);
        
        $this->assertNotNull($bindex1);
        $this->assertEquals($bindex1, $bindex2, "El Blind Index debe ser determinista e idéntico para la misma CURP.");
        $this->assertEquals(64, strlen($bindex1), "El Blind Index HMAC-SHA256 debe tener exactamente 64 caracteres hexadecimales.");

        // Prueba de insensibilidad a minúsculas / espacios
        $bindexMinusculas = Encryption::getBlindIndex("  abcd010101hdfrrn09  ");
        $this->assertEquals($bindex1, $bindexMinusculas, "El Blind Index debe normalizar a mayúsculas y sin espacios.");
    }

    /**
     * Valida que dos textos diferentes generen Blind Indexes distintos.
     */
    public function testBlindIndexDiferenteParaCurpsDistintas() {
        $bindex1 = Encryption::getBlindIndex("ABCD010101HDFRRN09");
        $bindex2 = Encryption::getBlindIndex("XYZW991231MDFRRN01");
        
        $this->assertNotEquals($bindex1, $bindex2);
    }

    /**
     * Valida el ciclo completo de encriptación y desencriptación simétrica.
     */
    public function testEncryptAndDecrypt() {
        $textoPlano = "CURP_DE_PRUEBA_2026_MEX";
        
        $cifrado = Encryption::encrypt($textoPlano);
        $this->assertNotNull($cifrado);
        $this->assertNotEquals($textoPlano, $cifrado);
        
        $descifrado = Encryption::decrypt($cifrado);
        $this->assertEquals($textoPlano, $descifrado);
    }

    /**
     * Valida firmas digitales HMAC en tiempo constante para validación de actas QR.
     */
    public function testFirmasDigitalesHMAC() {
        $data = "ACTA_NACIMIENTO_FOLIO_2026_0001";
        $firma = Encryption::sign($data);
        
        $this->assertNotEmpty($firma);
        $this->assertTrue(Encryption::verifySignature($data, $firma));
        
        // Firma con datos alterados debe fallar
        $this->assertFalse(Encryption::verifySignature("ACTA_NACIMIENTO_FOLIO_2026_0002", $firma));
        $this->assertFalse(Encryption::verifySignature($data, "firma_falsa_1234567890abcdef"));
    }
}
