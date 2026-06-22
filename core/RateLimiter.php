<?php
namespace Core;

/**
 * Clase RateLimiter encargada de limitar el número de peticiones por minuto
 * para proteger endpoints sensibles contra scraping o ataques de denegación de servicio.
 * Utiliza la capa de caché del sistema (Redis/Memcached o archivos).
 */
class RateLimiter {
    /**
     * Comprueba si la petición actual del cliente está dentro de los límites permitidos.
     * 
     * @param string $endpoint Identificador del endpoint a proteger
     * @param int $maxRequests Número máximo de peticiones permitidas en la ventana
     * @param int $decaySeconds Tamaño de la ventana de tiempo en segundos
     * @return bool True si la petición es permitida, False si es bloqueada
     */
    public static function check($endpoint, $maxRequests = 30, $decaySeconds = 60) {
        // Obtener la dirección IP del cliente
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Generar una clave de caché única para la combinación de IP y endpoint
        $cacheKey = "ratelimit_" . md5($endpoint . "_" . $ip);
        
        $data = Cache::get($cacheKey);
        $now = time();
        
        if ($data === null) {
            // Primer registro o ventana expirada
            $payload = [
                'hits' => 1,
                'reset_time' => $now + $decaySeconds
            ];
            Cache::set($cacheKey, $payload, $decaySeconds);
            return true;
        }
        
        // Si el tiempo actual excede el tiempo de reseteo (por si acaso), reiniciar ventana
        if ($now > $data['reset_time']) {
            $payload = [
                'hits' => 1,
                'reset_time' => $now + $decaySeconds
            ];
            Cache::set($cacheKey, $payload, $decaySeconds);
            return true;
        }
        
        // Incrementar el contador de peticiones
        $data['hits']++;
        
        // Calcular el tiempo restante de la ventana
        $remainingTime = $data['reset_time'] - $now;
        if ($remainingTime <= 0) {
            $remainingTime = 1;
        }
        
        // Guardar contador actualizado con el tiempo restante como TTL
        Cache::set($cacheKey, $data, $remainingTime);
        
        // Bloquear si excede el límite
        if ($data['hits'] > $maxRequests) {
            return false;
        }
        
        return true;
    }
}
