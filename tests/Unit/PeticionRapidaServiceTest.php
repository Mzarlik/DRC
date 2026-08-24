<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Services\PeticionRapidaService;

/**
 * Pruebas unitarias del catálogo maestro de trámites de ventanilla y del
 * validador estricto de PeticionRapidaService.
 */
class PeticionRapidaServiceTest extends TestCase {
    private function datosValidos(): array {
        return [
            'solicitante_nombre' => 'maría de los ángeles pérez',
            'solicitante_curp' => 'GAPA050301HDFRRN09',
            'solicitante_telefono' => '5512345678',
            'tipo_peticion' => 'REGISTRO_NACIMIENTO',
            'detalle' => 'Copia certificada de acta'
        ];
    }

    // ---------- Catálogo de trámites ----------

    public function testCatalogoConEstructuraCompleta() {
        $this->assertNotEmpty(PeticionRapidaService::TRAMITES);
        foreach (PeticionRapidaService::TRAMITES as $clave => $tramite) {
            $this->assertArrayHasKey('codigo', $tramite, "Trámite $clave sin código.");
            $this->assertArrayHasKey('nombre', $tramite, "Trámite $clave sin nombre.");
            $this->assertMatchesRegularExpression('/^[A-Z]{2,4}$/', $tramite['codigo'], "Código inválido en $clave.");
            $this->assertNotSame('', trim($tramite['nombre']), "Nombre vacío en $clave.");
        }
    }

    public function testCodigosDeTramiteUnicos() {
        $codigos = array_column(PeticionRapidaService::TRAMITES, 'codigo');
        $this->assertSame($codigos, array_values(array_unique($codigos)), 'Existen códigos de trámite duplicados.');
    }

    public function testGetModuloPorTramiteDevuelveAlgoParaTodoElCatalogo() {
        foreach (array_keys(PeticionRapidaService::TRAMITES) as $clave) {
            $modulo = PeticionRapidaService::getModuloPorTramite($clave);
            $this->assertNotSame('', $modulo, "Trámite $clave sin módulo asignado.");
        }
    }

    // ---------- validar() ----------

    public function testDatosValidosNormalizados() {
        $res = PeticionRapidaService::validar($this->datosValidos());

        $this->assertTrue($res['valido'], 'Error inesperado: ' . ($res['error'] ?? ''));
        $this->assertNull($res['error']);

        $data = $res['data'];
        $this->assertSame('MARÍA DE LOS ÁNGELES PÉREZ', $data['solicitante_nombre'], 'Nombres deben normalizarse a MAYÚSCULAS.');
        $this->assertNotSame('GAPA050301HDFRRN09', $data['solicitante_curp'], 'La CURP debe almacenarse cifrada.');
        $this->assertSame('5512345678', $data['solicitante_telefono']);
        $this->assertSame('PENDIENTE', $data['estatus'], 'Estatus por defecto debe ser PENDIENTE.');
    }

    public function testNombreDemasiadoCorto() {
        $data = $this->datosValidos();
        $data['solicitante_nombre'] = 'AB';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('al menos 3 caracteres', $res['error']);
    }

    public function testNombreConCaracteresProhibidos() {
        $data = $this->datosValidos();
        $data['solicitante_nombre'] = 'JUAN <script>';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('solo debe contener letras', $res['error']);
    }

    public function testCurpConLongitudIncorrecta() {
        $data = $this->datosValidos();
        $data['solicitante_curp'] = 'GAPA050301HDFRRN0';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('exactamente 18', $res['error']);
    }

    public function testCurpConFormatoInvalido() {
        $data = $this->datosValidos();
        $data['solicitante_curp'] = '1234050301HDFRRN099';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('RENAPO', $res['error']);
    }

    public function testTelefonoConDigitosInsuficientes() {
        $data = $this->datosValidos();
        $data['solicitante_telefono'] = '55-1234';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('10 dígitos', $res['error']);
    }

    public function testTipoPeticionFueraDeCatalogo() {
        $data = $this->datosValidos();
        $data['tipo_peticion'] = 'TRAMITE_FANTASMA';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('cat', $res['error']);
    }

    public function testDetalleDemasiadoCorto() {
        $data = $this->datosValidos();
        $data['detalle'] = 'AB';
        $res = PeticionRapidaService::validar($data);
        $this->assertFalse($res['valido']);
        $this->assertStringContainsString('al menos 4 caracteres', $res['error']);
    }

    public function testEstatusInvalidoCaeAPendiente() {
        $data = $this->datosValidos();
        $data['estatus'] = 'ESTATUS_FANTASMA';
        $res = PeticionRapidaService::validar($data);
        $this->assertTrue($res['valido']);
        $this->assertSame('PENDIENTE', $res['data']['estatus']);
    }
}
