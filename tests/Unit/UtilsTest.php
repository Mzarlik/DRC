<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Utils;

/**
 * Pruebas unitarias para la clase Core\Utils que validan
 * el cálculo de fechas de llegada y la validación de líneas de pago.
 */
class UtilsTest extends TestCase {
    /**
     * Prueba el cálculo de fecha de llegada sumando días naturales.
     */
    public function testCalcularFechaLlegadaCalendarDays() {
        $fecha = '2026-06-22'; // Es un Lunes
        // 15 días naturales a partir del lunes 22 de junio de 2026
        $res = Utils::calcularFechaLlegada($fecha, 15, false);
        $this->assertEquals('2026-07-07', $res);
    }

    /**
     * Prueba el cálculo de fecha de llegada sumando únicamente días hábiles.
     */
    public function testCalcularFechaLlegadaBusinessDays() {
        $fecha = '2026-06-22'; // Es un Lunes
        // 15 días hábiles a partir del lunes 22 de junio de 2026:
        // Semana 1 (5 días): Martes 23, Miércoles 24, Jueves 25, Viernes 26, Lunes 29 de junio
        // Semana 2 (5 días): Martes 30 de junio, Miércoles 1, Jueves 2, Viernes 3, Lunes 6 de julio
        // Semana 3 (5 días): Martes 7, Miércoles 8, Jueves 9, Viernes 10, Lunes 13 de julio
        $res = Utils::calcularFechaLlegada($fecha, 15, true);
        $this->assertEquals('2026-07-13', $res);
    }

    /**
     * Prueba las reglas de validación de longitud y formato de la línea de pago.
     */
    public function testValidarLineaPago() {
        // Casos válidos (alfanumérico y longitud entre 17 y 25)
        $this->assertTrue(Utils::validarLineaPago('12345678901234567')); // 17 caracteres
        $this->assertTrue(Utils::validarLineaPago('ABCD1234EFGH5678IJKL')); // 20 caracteres
        $this->assertTrue(Utils::validarLineaPago('1234567890123456789012345')); // 25 caracteres
        
        // Casos inválidos por longitud
        $this->assertFalse(Utils::validarLineaPago('1234567890123456')); // 16 caracteres (muy corto)
        $this->assertFalse(Utils::validarLineaPago('12345678901234567890123456')); // 26 caracteres (muy largo)
        
        // Casos inválidos por caracteres especiales
        $this->assertFalse(Utils::validarLineaPago('12345678901234567-')); // Contiene guión
        $this->assertFalse(Utils::validarLineaPago('12345678901234567_')); // Contiene guión bajo
        $this->assertFalse(Utils::validarLineaPago('1234567890 1234567')); // Contiene espacio
    }
}
