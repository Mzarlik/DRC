<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\RateLimiter;
use Core\Cache;

/**
 * Pruebas unitarias del Rate Limiter por IP: ventana de peticiones,
 * bloqueo al exceder el límite y reinicio de ventana al expirar.
 */
class RateLimiterTest extends TestCase {
    private array $clavesLimpieza = [];

    protected function setUp(): void {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '10.255.255.' . random_int(1, 254);
    }

    protected function tearDown(): void {
        foreach ($this->clavesLimpieza as $clave) {
            Cache::delete($clave);
        }
        unset($_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    /**
     * Clave interna de caché que usa RateLimiter (replicada para manipular la ventana).
     */
    private function cacheKey(string $endpoint): string {
        return 'ratelimit_' . md5($endpoint . '_' . $_SERVER['REMOTE_ADDR']);
    }

    public function testPrimeraPeticionPermitida() {
        $endpoint = 'test_rl_' . uniqid();
        $this->clavesLimpieza[] = $this->cacheKey($endpoint);
        $this->assertTrue(RateLimiter::check($endpoint, 5, 60));
    }

    public function testBloqueoAlExcederElLimite() {
        $endpoint = 'test_rl_' . uniqid();
        $this->clavesLimpieza[] = $this->cacheKey($endpoint);

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(RateLimiter::check($endpoint, 5, 60), "Petición #$i debe permitirse.");
        }

        $this->assertFalse(RateLimiter::check($endpoint, 5, 60), 'La petición 6 debe bloquearse.');
        $this->assertFalse(RateLimiter::check($endpoint, 5, 60), 'La petición 7 también debe bloquearse.');
    }

    public function testVentanasIndependientesPorEndpointYIp() {
        $endpointA = 'test_rl_a_' . uniqid();
        $endpointB = 'test_rl_b_' . uniqid();
        $this->clavesLimpieza[] = $this->cacheKey($endpointA);
        $this->clavesLimpieza[] = $this->cacheKey($endpointB);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check($endpointA, 5, 60);
        }
        $this->assertFalse(RateLimiter::check($endpointA, 5, 60));
        $this->assertTrue(RateLimiter::check($endpointB, 5, 60), 'Otro endpoint no hereda el conteo.');
    }

    public function testReinicioDeVentanaExpirada() {
        $endpoint = 'test_rl_' . uniqid();
        $clave = $this->cacheKey($endpoint);
        $this->clavesLimpieza[] = $clave;

        // Agotar la ventana
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::check($endpoint, 3, 60);
        }
        $this->assertFalse(RateLimiter::check($endpoint, 3, 60));

        // Simular que la ventana expiró (reset_time en el pasado) con muchos hits acumulados
        Cache::set($clave, ['hits' => 999, 'reset_time' => time() - 10], 60);

        $this->assertTrue(RateLimiter::check($endpoint, 3, 60), 'Ventana expirada debe reiniciar el contador.');

        // Tras el reinicio, el contador arranca desde 1: quedan 2 peticiones antes del bloqueo
        $this->assertTrue(RateLimiter::check($endpoint, 3, 60));
        $this->assertTrue(RateLimiter::check($endpoint, 3, 60));
        $this->assertFalse(RateLimiter::check($endpoint, 3, 60));
    }
}
