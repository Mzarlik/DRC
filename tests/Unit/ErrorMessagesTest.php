<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Services\ErrorMessages;

/**
 * Pruebas unitarias del traductor de errores técnicos a mensajes de usuario
 * (ErrorMessages::humanize / humanizeText). Garantiza que NUNCA se filtre
 * el mensaje técnico original en las respuestas al usuario.
 */
class ErrorMessagesTest extends TestCase {
    public function testErrorDeConexion() {
        $e = new \PDOException('SQLSTATE[HY000] [2002] Connection refused', 2002);
        $msg = ErrorMessages::humanize($e);
        $this->assertStringContainsString('No se pudo conectar', $msg);
        $this->assertStringNotContainsString('2002', $msg);
    }

    public function testAccesoDenegadoBaseDatos() {
        $e = new \PDOException('Access denied for user \'drc\'@\'localhost\'', 1045);
        $this->assertStringContainsString('No se pudo conectar', ErrorMessages::humanize($e));
    }

    public function testRegistroDuplicado() {
        $e = new \PDOException('SQLSTATE[23000]: Duplicate entry \'ACTA-1\' for key \'numero_acta\'', 23000);
        $msg = ErrorMessages::humanize($e);
        $this->assertStringContainsString('Ya existe un registro', $msg);
        $this->assertStringNotContainsString('ACTA-1', $msg, 'No debe filtrar el valor duplicado.');
    }

    public function testLlaveForanea() {
        $e = new \PDOException('SQLSTATE[23000]: a foreign key constraint fails', 23000);
        $this->assertStringContainsString('relacionado con otros tr', ErrorMessages::humanize($e));
    }

    public function testColumnaDesconocida() {
        $e = new \PDOException('SQLSTATE[42S22]: Unknown column \'x\' in \'field list\'', '42S22');
        $this->assertStringContainsString('migraciones pendientes', ErrorMessages::humanize($e));
    }

    public function testTablaInexistente() {
        $e = new \PDOException("SQLSTATE[42S02]: Base table or view not found: tabla 'drc.jobs2'", '42S02');
        $this->assertStringContainsString('migraciones pendientes', ErrorMessages::humanize($e));
    }

    public function testLockWaitTimeout() {
        $e = new \PDOException('SQLSTATE[HY000]: Lock wait timeout exceeded', 1205);
        $this->assertStringContainsString('tard', ErrorMessages::humanize($e));
    }

    public function testDataTooLong() {
        $e = new \PDOException('SQLSTATE[22001]: Data too long for column', 22001);
        $this->assertStringContainsString('longitud m', ErrorMessages::humanize($e));
    }

    public function testFechaInvalida() {
        $e = new \PDOException('SQLSTATE[22007]: Incorrect date value: \'xx\'', 22007);
        $this->assertStringContainsString('fecha', strtolower(ErrorMessages::humanize($e)));
    }

    public function testErrorDesconocidoDevuelveGenerico() {
        $e = new \RuntimeException('Stack trace interno con ruta C:\xampp\htdocs\DRC\core\X.php:88');
        $this->assertSame(ErrorMessages::GENERICO, ErrorMessages::humanize($e));
        $this->assertStringNotContainsString('C:\\xampp', ErrorMessages::humanize($e));
    }

    // ---------- humanizeText ----------

    public function testHumanizeTextDuplicado() {
        $this->assertStringContainsString('Registro duplicado', ErrorMessages::humanizeText('SQLSTATE[23000]: Duplicate entry'));
    }

    public function testHumanizeTextFk() {
        $this->assertStringContainsString('Registro relacionado', ErrorMessages::humanizeText('Cannot delete: a foreign key constraint fails'));
    }

    public function testHumanizeTextSqlstateGenerico() {
        $this->assertSame(ErrorMessages::GENERICO, ErrorMessages::humanizeText('SQLSTATE[08006]'));
    }

    public function testHumanizeTextMensajeYaEntendibleSeConserva() {
        $mensaje = 'El usuario no tiene permisos para esta operaci';
        $this->assertSame($mensaje, ErrorMessages::humanizeText($mensaje));
    }

    public function testHumanizeTextVacioDevuelveGenerico() {
        $this->assertSame(ErrorMessages::GENERICO, ErrorMessages::humanizeText(''));
    }
}
