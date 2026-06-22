<?php
namespace Core;

/**
 * Clase encargada de la encriptación y desencriptación simétrica de datos
 * utilizando AES-256-CBC de forma determinista para permitir búsquedas exactas e índices.
 */
class Encryption {
    private static $key = null;

    /**
     * Obtiene y deriva la clave criptográfica de 32 bytes desde las variables de entorno.
     */
    private static function getKey() {
        if (self::$key === null) {
            $envPath = dirname(__DIR__) . '/.env';
            $key = null;
            if (file_exists($envPath)) {
                $env = parse_ini_file($envPath);
                if (isset($env['ENCRYPTION_KEY'])) {
                    $key = $env['ENCRYPTION_KEY'];
                }
            }
            if (empty($key)) {
                $key = 'drc_system_secure_aes256_key_2026';
            }
            // Derivación segura usando SHA-256 para obtener 32 bytes exactos
            self::$key = hash('sha256', $key, true);
        }
        return self::$key;
    }

    /**
     * Encripta una cadena de texto usando AES-256-CBC de forma determinista.
     * El IV se genera usando HMAC-SHA-256 del texto plano con la clave derivada.
     * 
     * @param string $data Texto plano a encriptar
     * @return string|null Ciphertext codificado en base64
     */
    public static function encrypt($data) {
        if ($data === null || $data === '') {
            return null;
        }

        $key = self::getKey();
        
        // Generar un IV determinista para permitir búsquedas e indexación en base de datos.
        // Se extraen los primeros 16 bytes de la firma HMAC.
        $iv = substr(hash_hmac('sha256', $data, $key, true), 0, 16);
        
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        // Retornar concatenación del IV y el ciphertext codificado en base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencripta un ciphertext en base64. Si no tiene el formato correcto o no
     * puede ser desencriptado, devuelve el valor original de forma segura (retrocompatibilidad).
     * 
     * @param string $data Ciphertext codificado en base64
     * @return string|null Texto plano desencriptado o el valor original
     */
    public static function decrypt($data) {
        if ($data === null || $data === '') {
            return null;
        }

        $key = self::getKey();
        $decoded = base64_decode($data, true);
        
        // Validar tamaño mínimo (16 bytes IV + al menos 1 bloque de 16 bytes ciphertext)
        if ($decoded === false || strlen($decoded) < 32) {
            return $data; // Si no es base64 válido o es muy corto, retornar tal cual
        }

        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        return $decrypted !== false ? $decrypted : $data;
    }
}
