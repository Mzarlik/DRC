<?php
// scripts/generate_keys.php
/**
 * Generador seguro de llaves criptográficas para el archivo .env del ERP DRC.
 * Genera claves de 32 bytes (256 bits = 64 caracteres hexadecimales) de alta entropía.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

echo "=================================================================\n";
echo " GENERADOR DE LLAVES CRIPTOGRÁFICAS — ERP REGISTRO CIVIL (DRC)  \n";
echo "=================================================================\n\n";

$encryptionKey = bin2hex(random_bytes(32));
$blindIndexKey = bin2hex(random_bytes(32));
$cronSecret    = bin2hex(random_bytes(32));

echo "Copie y pegue los siguientes valores en su archivo .env de producción:\n\n";
echo "ENCRYPTION_KEY=" . $encryptionKey . "\n";
echo "BLIND_INDEX_KEY=" . $blindIndexKey . "\n";
echo "CRON_SECRET=" . $cronSecret . "\n\n";

echo "=================================================================\n";
echo " NOTA: Guarde estas claves en un lugar seguro. Si cambia la clave \n";
echo " ENCRYPTION_KEY después de guardar datos, no podrá descifrarlos.  \n";
echo "=================================================================\n";
