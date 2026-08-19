<?php
namespace Core\Services;

/**
 * Servicio de Firma Electrónica Avanzada (FIEL / e.firma / PKI X.509)
 * para el sellado asimétrico de documentos y actas oficiales mediante OpenSSL.
 */
class FirmaElectronicaService {

    /**
     * Sella un documento digital usando la llave privada (.key) del Oficial o Dependencia.
     *
     * @param string $dataToSign Cadena original o digest del documento
     * @param string $privateKeyPem Contenido de la llave privada en formato PEM
     * @param string $passphrase Contraseña de la llave privada (si está protegida)
     * @return string Sello digital en Base64
     * @throws \RuntimeException Si la llave privada no es válida
     */
    public static function firmarCadena(string $dataToSign, string $privateKeyPem, string $passphrase = ''): string {
        $privateKey = openssl_pkey_get_private($privateKeyPem, $passphrase);
        if (!$privateKey) {
            throw new \RuntimeException("No se pudo cargar la llave privada. Verifique el formato PEM y la contraseña.");
        }

        $signature = '';
        $success = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        if (!$success) {
            throw new \RuntimeException("Error al generar el sello digital con OpenSSL.");
        }

        return base64_encode($signature);
    }

    /**
     * Verifica la validez jurídica del sello usando el certificado público (.cer) o llave pública.
     *
     * @param string $data Cadena original firmada
     * @param string $signatureBase64 Sello digital en Base64
     * @param string $publicKeyOrCertPem Certificado público (.cer) o llave pública en formato PEM
     * @return bool True si el sello es auténtico e inalterado
     */
    public static function verificarFirma(string $data, string $signatureBase64, string $publicKeyOrCertPem): bool {
        $publicKey = openssl_pkey_get_public($publicKeyOrCertPem);
        if (!$publicKey) {
            return false;
        }

        $rawSignature = base64_decode($signatureBase64, true);
        if ($rawSignature === false) {
            return false;
        }

        $result = openssl_verify($data, $rawSignature, $publicKey, OPENSSL_ALGO_SHA256);
        return ($result === 1);
    }

    /**
     * Helper para generar un par de llaves RSA de prueba (útil para pruebas unitarias y desarrollo).
     *
     * @param int $bits Longitud de la llave (por defecto 2048)
     * @return array ['private' => string, 'public' => string]
     */
    public static function generarParLlavesPrueba(int $bits = 2048): array {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $possibleCnf = [
            getenv('OPENSSL_CONF'),
            'C:/xampp/apache/bin/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/etc/pki/tls/openssl.cnf'
        ];
        foreach ($possibleCnf as $cnf) {
            if ($cnf && file_exists($cnf)) {
                $config['config'] = $cnf;
                break;
            }
        }

        $res = openssl_pkey_new($config);
        if ($res === false) {
            throw new \RuntimeException("No se pudo inicializar openssl_pkey_new. Verifique la configuración de openssl.cnf.");
        }

        $privateKey = '';
        openssl_pkey_export($res, $privateKey, null, $config);
        $details = openssl_pkey_get_details($res);
        $publicKey = $details['key'] ?? '';

        return [
            'private' => $privateKey,
            'public'  => $publicKey
        ];
    }
}
