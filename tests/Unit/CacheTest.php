<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Cache;

/**
 * Pruebas unitarias para la capa de caché (Redis/Memcached → fallback a archivos).
 * Verifican el ciclo completo set/get/delete, la sobrescritura, la sanitización
 * de claves y la expiración por TTL, de forma agnóstica al driver activo.
 */
class CacheTest extends TestCase {
    private array $keysUsadas = [];

    protected function tearDown(): void {
        foreach ($this->keysUsadas as $key) {
            Cache::delete($key);
        }
        parent::tearDown();
    }

    private function key(string $sufijo): string {
        $key = 'test_cache_' . uniqid() . '_' . $sufijo;
        $this->keysUsadas[] = $key;
        return $key;
    }

    public function testTipoDriverValido() {
        $tipo = Cache::getType();
        $this->assertContains($tipo, ['redis', 'memcached', 'file']);
    }

    public function testSetGetRoundtripString() {
        $key = $this->key('string');
        $this->assertTrue(Cache::set($key, 'valor_de_prueba', 60));
        $this->assertSame('valor_de_prueba', Cache::get($key));
    }

    public function testSetGetRoundtripArray() {
        $key = $this->key('array');
        $valor = ['hits' => 3, 'reset_time' => time() + 60, 'anidado' => ['a' => 1]];
        $this->assertTrue(Cache::set($key, $valor, 60));
        $this->assertSame($valor, Cache::get($key));
    }

    public function testSobrescrituraDeValor() {
        $key = $this->key('overwrite');
        Cache::set($key, 'primero', 60);
        Cache::set($key, 'segundo', 60);
        $this->assertSame('segundo', Cache::get($key));
    }

    public function testClaveInexistenteDevuelveNull() {
        $this->assertNull(Cache::get('clave_que_nunca_existe_' . uniqid()));
    }

    public function testDeleteEliminaElValor() {
        $key = $this->key('delete');
        Cache::set($key, 'temporal', 60);
        $this->assertSame('temporal', Cache::get($key));

        $this->assertTrue(Cache::delete($key));
        $this->assertNull(Cache::get($key));
    }

    public function testSanitizacionDeClavesConCaracteresEspeciales() {
        $key = $this->key('clave.con:caracteres/especiales');
        Cache::set($key, 'ok', 60);
        $this->assertSame('ok', Cache::get($key));
    }

    public function testExpiracionPorTtl() {
        $key = $this->key('ttl');
        Cache::set($key, 'efimero', 1);
        $this->assertSame('efimero', Cache::get($key));

        sleep(2);
        $this->assertNull(Cache::get($key), 'El valor debe expirar tras el TTL indicado.');
    }
}
