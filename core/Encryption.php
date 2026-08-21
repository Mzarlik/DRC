<?php
namespace Core;

/**
 * Clase encargada de la encriptación simétrica AES-256-CBC, generación de Blind Index
 * con HMAC y verificación de firmas digitales para el ERP DRC.
 */
class Encryption {
    private static $key = null;
    private static $blindKey = null;

    /**
     * Obtiene y deriva la clave criptográfica principal de 32 bytes desde las variables de entorno.
     *
     * @throws \RuntimeException si falta ENCRYPTION_KEY en .env
     */
    private static function getKey() {
        if (self::$key === null) {
            $key = self::loadEnvKey('ENCRYPTION_KEY');
            if (empty($key)) {
                $isTesting = defined('PHPUNIT_COMPOSER_INSTALL') || class_exists('\\PHPUnit\\Framework\\TestCase');
                if ($isTesting) {
                    // Clave solo válida en pruebas unitarias; nunca en producción.
                    $key = 'drc_testing_only_key_do_not_use_in_production';
                } else {
                    throw new \RuntimeException('ENCRYPTION_KEY no está configurada en .env. El sistema rechaza operar con claves por defecto.');
                }
            }
            // Derivación segura usando SHA-256 para obtener 32 bytes exactos
            self::$key = hash('sha256', $key, true);
        }
        return self::$key;
    }

    /**
     * Lee una clave desde .env y, como respaldo, desde variables de entorno del sistema.
     */
    private static function loadEnvKey($name) {
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            $env = @parse_ini_file($envPath);
            if ($env !== false && isset($env[$name]) && $env[$name] !== '') {
                return $env[$name];
            }
        }
        $fromEnv = getenv($name);
        return $fromEnv !== false && $fromEnv !== '' ? $fromEnv : null;
    }

    /**
     * Obtiene y deriva la clave para Blind Index (HMAC) desde las variables de entorno.
     */
    private static function getBlindKey() {
        if (self::$blindKey === null) {
            $blindKey = self::loadEnvKey('BLIND_INDEX_KEY');
            if (empty($blindKey)) {
                // Derivación determinista desde la clave maestra configurada en .env
                // (mantiene compatibilidad con los blind index ya almacenados).
                // Para usar una sal dedicada, definir BLIND_INDEX_KEY y regenerar
                // los índices con migración de reíndexado.
                $blindKey = hash_hmac('sha256', 'blind_index_salt_drc', self::getKey(), true);
            }
            self::$blindKey = hash('sha256', $blindKey, true);
        }
        return self::$blindKey;
    }

    /**
     * Genera un Blind Index determinista e irreversible para búsquedas exactas e índices UNIQUE.
     *
     * @param string|null $data Texto plano (ej. CURP)
     * @return string|null Hash HMAC en formato hexadecimal
     */
    public static function getBlindIndex($data) {
        if ($data === null || $data === '') {
            return null;
        }
        $clean = mb_strtoupper(trim($data), 'UTF-8');
        return hash_hmac('sha256', $clean, self::getBlindKey());
    }

    /**
     * Encripta una cadena de texto usando AES-256-CBC de forma determinista.
     * El IV se genera usando HMAC-SHA-256 del texto plano con la clave derivada.
     * 
     * @param string|null $data Texto plano a encriptar
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
     * @param string|null $data Ciphertext codificado en base64
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

    /**
     * Genera una firma HMAC-SHA256 para un dato (usada en tokens de validación pública de actas).
     *
     * @param string $data Contenido a firmar
     * @return string Firma hexadecimal
     */
    public static function sign($data) {
        return hash_hmac('sha256', $data, self::getKey());
    }

    /**
     * Verifica con comparación en tiempo constante que la firma pertenezca al dato.
     *
     * @param string $data Contenido firmado
     * @param string $signature Firma a verificar
     * @return bool
     */
    public static function verifySignature($data, $signature) {
        if (!is_string($signature) || $signature === '') {
            return false;
        }
        return hash_equals(self::sign($data), $signature);
    }
}
