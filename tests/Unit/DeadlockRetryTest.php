<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Database;

/**
 * Pruebas unitarias para la mitigación y recuperación automática
 * ante Deadlocks (MySQL 1213 / 40001) y Timeouts en Core\Database.
 */
class DeadlockRetryTest extends TestCase {
    /**
     * Prueba que transactionWithRetry se recupere ante un Deadlock simulado
     * y finalice exitosamente en el siguiente intento.
     */
    public function testRecuperacionAutomaticaAnteDeadlock() {
        $intentos = 0;
        
        $resultado = Database::transactionWithRetry(function($pdo) use (&$intentos) {
            $intentos++;
            if ($intentos < 2) {
                // Simular excepción de Deadlock MySQL 1213
                throw new \PDOException("Deadlock found when trying to get lock; try restarting transaction", 1213);
            }
            return "OPERACION_TRANSACCIONAL_EXITOSA";
        }, 3);

        $this->assertEquals("OPERACION_TRANSACCIONAL_EXITOSA", $resultado);
        $this->assertEquals(2, $intentos, "El helper debió reintentar automáticamente tras el primer fallo de Deadlock.");
    }

    /**
     * Prueba que transactionWithRetry lance la excepción si se superan los reintentos permitidos.
     */
    public function testFalloSiExcedeMaxReintentos() {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Deadlock found");

        $intentos = 0;
        Database::transactionWithRetry(function($pdo) use (&$intentos) {
            $intentos++;
            throw new \PDOException("Deadlock found when trying to get lock; try restarting transaction", 1213);
        }, 2);
    }

    /**
     * Prueba que las excepciones no relacionadas con concurrencia no se reintenten.
     */
    public function testNoReintentaErroresComunes() {
        $intentos = 0;
        try {
            Database::transactionWithRetry(function($pdo) use (&$intentos) {
                $intentos++;
                throw new \RuntimeException("Error de validación de negocio");
            }, 3);
        } catch (\RuntimeException $e) {
            $this->assertEquals("Error de validación de negocio", $e->getMessage());
        }

        $this->assertEquals(1, $intentos, "Los errores que no son de Deadlock no deben reintentarse.");
    }
}
