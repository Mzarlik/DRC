<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Database;

/**
 * Pruebas unitarias para la clase Core\Database que garantizan
 * la conectividad y la transaccionalidad de folios.
 */
class DatabaseTest extends TestCase {
    /**
     * Prueba que el método getConnection devuelva una instancia de PDO válida.
     */
    public function testGetConnectionReturnsPDO() {
        $pdo = Database::getConnection();
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    /**
     * Prueba que el método getReadConnection devuelva una instancia de PDO válida.
     */
    public function testGetReadConnectionReturnsPDO() {
        $pdo = Database::getReadConnection();
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    /**
     * Prueba que el método getWriteConnection devuelva una instancia de PDO válida.
     */
    public function testGetWriteConnectionReturnsPDO() {
        $pdo = Database::getWriteConnection();
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    /**
     * Prueba que la generación de folios correlativos maneje correctamente la secuencia
     * de forma atómica y transaccional.
     */
    public function testGenerateFolio() {
        $modulo = 'test_unit_' . uniqid();
        
        // Generar primer folio
        $folio1 = Database::generateFolio($modulo, 'TEST-', 4);
        $this->assertEquals('TEST-0001', $folio1);
        
        // Generar segundo folio y verificar correlatividad
        $folio2 = Database::generateFolio($modulo, 'TEST-', 4);
        $this->assertEquals('TEST-0002', $folio2);
        
        // Limpiar base de datos después de la prueba
        $pdo = Database::getWriteConnection();
        $pdo->prepare("DELETE FROM folios_secuencia WHERE modulo = ?")->execute([$modulo]);
    }
}
