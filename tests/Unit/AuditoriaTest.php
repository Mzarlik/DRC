<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Auditoria;
use Core\Database;

/**
 * Pruebas unitarias para la clase Core\Auditoria que garantizan
 * el correcto registro de acciones de auditoría y logs de errores.
 */
class AuditoriaTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Spoof active session
        $_SESSION['user_id'] = 1; // ID de un usuario existente o por defecto en BD
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/test-uri';
    }

    protected function tearDown(): void {
        // Limpiar base de datos
        $pdo = Database::getWriteConnection();
        $pdo->prepare("DELETE FROM auditoria_logs WHERE modulo = 'TEST_UNIT'")->execute();
        $pdo->prepare("DELETE FROM error_logs WHERE mensaje LIKE 'TEST_UNIT%'")->execute();
        
        unset($_SESSION['user_id']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    /**
     * Prueba que logAccion registre correctamente la acción en la base de datos.
     */
    public function testLogAccion() {
        $modulo = 'TEST_UNIT';
        $accion = 'CREAR';
        $detalles = 'Detalle de prueba unitaria ' . uniqid();

        Auditoria::logAccion($modulo, $accion, $detalles);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM auditoria_logs WHERE modulo = ? AND detalles = ?");
        $stmt->execute([$modulo, $detalles]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row);
        $this->assertEquals(1, $row['usuario_id']);
        $this->assertEquals($modulo, $row['modulo']);
        $this->assertEquals($accion, $row['accion']);
        $this->assertEquals($detalles, $row['detalles']);
        $this->assertEquals('127.0.0.1', $row['ip_address']);
    }

    /**
     * Prueba que logError registre correctamente un error en la base de datos.
     */
    public function testLogError() {
        $mensaje = 'TEST_UNIT_ERROR: Mensaje de error de prueba';
        $archivo = 'test_file.php';
        $linea = 42;
        $stackTrace = 'Stack trace mock';

        Auditoria::logError($mensaje, $archivo, $linea, $stackTrace);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM error_logs WHERE mensaje = ?");
        $stmt->execute([$mensaje]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row);
        $this->assertEquals(1, $row['usuario_id']);
        $this->assertEquals($mensaje, $row['mensaje']);
        $this->assertEquals($archivo, $row['archivo']);
        $this->assertEquals($linea, $row['linea']);
        $this->assertEquals($stackTrace, $row['stack_trace']);
        $this->assertEquals('/test-uri', $row['url']);
        $this->assertEquals('127.0.0.1', $row['ip_address']);
    }
}
