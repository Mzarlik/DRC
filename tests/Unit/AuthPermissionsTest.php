<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Auth;

/**
 * Pruebas unitarias del sistema de permisos granulares (banderas booleanas),
 * del rol ADMIN, de la autorización de exportación y del ciclo CSRF.
 */
class AuthPermissionsTest extends TestCase {
    private array $clavesSesion = [];

    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = 1;
        $_SESSION['user_rol'] = 'OPERADOR';
    }

    protected function tearDown(): void {
        foreach ($this->clavesSesion as $clave) {
            unset($_SESSION[$clave]);
        }
        unset($_SESSION['user_id'], $_SESSION['user_rol'], $_SESSION['csrf_token']);
        $this->clavesSesion = [];
        parent::tearDown();
    }

    private function setSesion(array $valores): void {
        foreach ($valores as $clave => $valor) {
            $_SESSION[$clave] = $valor;
            $this->clavesSesion[] = $clave;
        }
    }

    // ---------- hasPermission ----------

    public function testSinBanderaNoHayPermiso() {
        $this->setSesion(['permiso_curp' => 0]);
        $this->assertFalse(Auth::hasPermission('permiso_curp'));
    }

    public function testBanderaActivaOtorgaPermiso() {
        $this->setSesion(['permiso_registro_nacimientos' => 1]);
        $this->assertTrue(Auth::hasPermission('permiso_registro_nacimientos'));
    }

    public function testBanderaInexistenteNoOtorgaPermiso() {
        $this->assertFalse(Auth::hasPermission('permiso_inexistente_xyz'));
    }

    public function testAdminTieneTodosLosPermisos() {
        $_SESSION['user_rol'] = 'ADMIN';
        $this->assertTrue(Auth::hasPermission('permiso_curp'));
        $this->assertTrue(Auth::hasPermission('permiso_cualquier_cosa_inexistente'));
    }

    // ---------- esCoordinador ----------

    public function testRolesCoordinadores() {
        foreach (['ADMIN', 'COORDINADOR', 'SUPERVISOR'] as $rol) {
            $_SESSION['user_rol'] = $rol;
            $this->assertTrue(Auth::esCoordinador(), "El rol $rol debe ser coordinador.");
        }
        $_SESSION['user_rol'] = 'OPERADOR';
        $this->assertFalse(Auth::esCoordinador());
    }

    // ---------- canExportar ----------

    public function testExportarConBandera() {
        $this->setSesion(['permiso_exportar' => 1]);
        $this->assertTrue(Auth::canExportar());
    }

    public function testExportarSinBanderaNiRol() {
        $this->setSesion(['permiso_exportar' => 0]);
        $this->assertFalse(Auth::canExportar());
    }

    public function testExportarPorRolCoordinadorSinBandera() {
        $_SESSION['user_rol'] = 'SUPERVISOR';
        $this->setSesion(['permiso_exportar' => 0]);
        $this->assertTrue(Auth::canExportar(), 'Coordinadores pueden exportar sin bandera individual.');
    }

    // ---------- CSRF ----------

    public function testCicloCompletoCsrf() {
        $token = Auth::generateCSRF();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token, 'Token CSRF debe ser 256 bits en hex.');
        $this->assertSame($token, Auth::generateCSRF(), 'generateCSRF debe ser estable dentro de la sesión.');

        $this->assertTrue(Auth::validateCSRF($token));
    }

    public function testCsrfRechazaTokensInvalidos() {
        Auth::generateCSRF();
        $this->assertFalse(Auth::validateCSRF('token_falso'));
        $this->assertFalse(Auth::validateCSRF(''));
        $this->assertFalse(Auth::validateCSRF(null));
        $this->assertFalse(Auth::validateCSRF(['array']));
    }
}
