<?php
namespace Core;

/**
 * Clase Cache que maneja el almacenamiento y recuperación de datos en memoria RAM (Redis/Memcached)
 * con fallback automático a archivos serializados en disco en caso de que no existan las extensiones
 * o el servidor correspondiente esté apagado.
 */
class Cache {
    private static $client = null;
    private static $type = null; // 'redis', 'memcached', o 'file'
    private static $cacheDir = null;

    /**
     * Inicializa el motor de caché adecuado según disponibilidad.
     */
    private static function init() {
        if (self::$type !== null) {
            return;
        }

        // 1. Intentar Redis
        if (class_exists('\Redis')) {
            try {
                $redis = new \Redis();
                // Tiempo límite de conexión corto de 1 segundo para evitar bloqueos
                if (@$redis->connect('127.0.0.1', 6379, 1.0)) {
                    self::$client = $redis;
                    self::$type = 'redis';
                    return;
                }
            } catch (\Exception $e) {
                // Silenciar e intentar el siguiente driver
            }
        }

        // 2. Intentar Memcached
        if (class_exists('\Memcached')) {
            try {
                $memcached = new \Memcached();
                $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000);
                if (@$memcached->addServer('127.0.0.1', 11211)) {
                    // Validar si el servidor realmente responde
                    $stats = $memcached->getStats();
                    if (!empty($stats) && isset($stats['127.0.0.1:11211'])) {
                        self::$client = $memcached;
                        self::$type = 'memcached';
                        return;
                    }
                }
            } catch (\Exception $e) {
                // Silenciar e intentar el siguiente driver
            }
        }

        // 3. Fallback: Caché por archivos serializados
        self::$type = 'file';
        self::$cacheDir = dirname(__DIR__) . '/cache';
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }
    }

    /**
     * Obtiene un valor de la caché por su clave.
     * 
     * @param string $key Clave de caché
     * @return mixed Valor guardado o null si expiró o no existe
     */
    public static function get($key) {
        self::init();
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);

        if (self::$type === 'redis') {
            $val = self::$client->get($safeKey);
            return $val !== false ? unserialize($val) : null;
        }

        if (self::$type === 'memcached') {
            $val = self::$client->get($safeKey);
            return self::$client->getResultCode() === \Memcached::RES_SUCCESS ? unserialize($val) : null;
        }

        // Caché en archivo
        $file = self::$cacheDir . '/' . $safeKey . '.cache';
        if (file_exists($file)) {
            $data = @file_get_contents($file);
            if ($data !== false) {
                $payload = unserialize($data);
                if (is_array($payload) && isset($payload['expire']) && isset($payload['data'])) {
                    if ($payload['expire'] === 0 || $payload['expire'] > time()) {
                        return $payload['data'];
                    } else {
                        @unlink($file); // Eliminar si ya expiró
                    }
                }
            }
        }
        return null;
    }

    /**
     * Guarda un valor en la caché.
     * 
     * @param string $key Clave de la caché
     * @param mixed $value Valor a guardar (cualquier tipo serializable)
     * @param int $ttl Tiempo de vida en segundos (0 para siempre)
     * @return bool True si se guardó con éxito, False de lo contrario
     */
    public static function set($key, $value, $ttl = 3600) {
        self::init();
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        $serialized = serialize($value);

        if (self::$type === 'redis') {
            if ($ttl > 0) {
                return self::$client->setex($safeKey, $ttl, $serialized);
            } else {
                return self::$client->set($safeKey, $serialized);
            }
        }

        if (self::$type === 'memcached') {
            $expire = $ttl > 0 ? time() + $ttl : 0;
            return self::$client->set($safeKey, $serialized, $expire);
        }

        // Caché en archivo
        $file = self::$cacheDir . '/' . $safeKey . '.cache';
        $payload = [
            'expire' => $ttl > 0 ? time() + $ttl : 0,
            'data' => $value
        ];
        return @file_put_contents($file, serialize($payload)) !== false;
    }

    /**
     * Elimina un valor de la caché.
     * 
     * @param string $key Clave de la caché
     * @return bool True si se borró, False si no existía o falló
     */
    public static function delete($key) {
        self::init();
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);

        if (self::$type === 'redis') {
            return self::$client->del($safeKey) > 0;
        }

        if (self::$type === 'memcached') {
            return self::$client->delete($safeKey);
        }

        // Caché en archivo
        $file = self::$cacheDir . '/' . $safeKey . '.cache';
        if (file_exists($file)) {
            return @unlink($file);
        }
        return false;
    }

    /**
     * Devuelve el tipo de motor de caché actualmente en uso.
     * 
     * @return string 'redis', 'memcached' o 'file'
     */
    public static function getType() {
        self::init();
        return self::$type;
    }
}
