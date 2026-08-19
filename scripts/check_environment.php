<?php
// scripts/check_environment.php
/**
 * Script de diagnóstico y verificación del entorno de ejecución del ERP DRC.
 * Valida versión de PHP, extensiones críticas, permisos de escritura y configuración base.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

echo "=================================================================\n";
echo " VERIFICADOR DE ENTORNO — ERP DIRECCIÓN DE REGISTRO CIVIL (DRC) \n";
echo "=================================================================\n\n";

$errors = [];
$warnings = [];

// 1. Versión de PHP
echo "[1] Versión de PHP: " . PHP_VERSION . " ... ";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "[OK]\n";
} else {
    echo "[ERROR]\n";
    $errors[] = "Se requiere PHP 8.2.0 o superior (versión actual: " . PHP_VERSION . ").";
}

// 2. Extensiones Obligatorias
echo "\n[2] Verificando Extensiones Obligatorias de PHP:\n";
$requiredExtensions = [
    'pdo' => 'Capa de acceso a datos PDO',
    'pdo_mysql' => 'Driver MySQL para PDO',
    'openssl' => 'Criptografía simétrica AES-256 y HMAC',
    'mbstring' => 'Manejo de cadenas UTF-8 y grafías indígenas',
    'gd' => 'Generación de códigos QR e imágenes de actas',
    'zip' => 'Exportación de reportes de Excel en .xlsx',
    'curl' => 'Peticiones HTTP seguras',
    'json' => 'Serialización de APIs y payloads de jobs',
    'fileinfo' => 'Validación MIME de documentos adjuntos',
    'zlib' => 'Compresión y descompresión de datos'
];

foreach ($requiredExtensions as $ext => $desc) {
    echo "  • Extensión '$ext' ($desc): ";
    if (extension_loaded($ext)) {
        echo "[OK]\n";
    } else {
        echo "[FALTA]\n";
        $errors[] = "Extensión obligatoria no cargada: '$ext'. Habilítela en php.ini.";
    }
}

// 3. Extensiones Opcionales de Rendimiento
echo "\n[3] Verificando Extensiones Opcionales / Aceleradores:\n";
$optionalExtensions = [
    'opcache' => 'Zend OPcache (Acelerador de compilación en RAM)',
    'redis' => 'Driver nativo de Redis (Sesiones y caché en RAM)',
    'memcached' => 'Driver nativo de Memcached (Caché secundaria)'
];

foreach ($optionalExtensions as $ext => $desc) {
    echo "  • Extensión '$ext' ($desc): ";
    if (extension_loaded($ext)) {
        echo "[ACTIVA]\n";
    } else {
        echo "[NO DISPONIBLE]\n";
        $warnings[] = "Extensión opcional no cargada: '$ext'. (El sistema utilizará fallback de archivos).";
    }
}

// 4. Permisos de Directorios de Escritura
echo "\n[4] Verificando Permisos de Escritura en Directorios del Sistema:\n";
$baseDir = dirname(__DIR__);
$directories = [
    $baseDir . '/cache' => 'Caché de fallback y catálogos',
    $baseDir . '/logs' => 'Bitácora local de errores',
    $baseDir . '/public/exports' => 'Almacenamiento de reportes .xlsx generados por el Worker',
    $baseDir . '/public/reports' => 'Almacenamiento de PDFs temporales'
];

foreach ($directories as $dir => $label) {
    echo "  • " . basename($dir) . "/ ($label): ";
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        echo "[ESCRIBIBLE]\n";
    } else {
        echo "[ERROR DE ESCRITURA]\n";
        $errors[] = "El directorio '$dir' no tiene permisos de escritura para PHP.";
    }
}

// 5. Verificación de Archivos de Configuración
echo "\n[5] Archivos de Configuración:\n";
$envFile = $baseDir . '/.env';
$envExample = $baseDir . '/.env.example';

echo "  • Archivo .env.example: ";
if (file_exists($envExample)) {
    echo "[PRESENTE]\n";
} else {
    echo "[FALTA]\n";
    $warnings[] = "Falta el archivo plantilla .env.example.";
}

echo "  • Archivo .env: ";
if (file_exists($envFile)) {
    echo "[CONFIGURADO]\n";
} else {
    echo "[NO DETECTADO] (Se usará configuración por defecto para desarrollo)\n";
}

// Resumen Final
echo "\n=================================================================\n";
echo " RESUMEN DEL DIAGNÓSTICO DEL ENTORNO                            \n";
echo "=================================================================\n";

if (empty($errors)) {
    echo " RESULTADO: ¡EL ENTORNO CUMPLE TODOS LOS REQUISITOS OBLIGATORIOS!\n";
    if (!empty($warnings)) {
        echo "\n Advertencias / Recomendaciones de optimización:\n";
        foreach ($warnings as $w) {
            echo "  ⚠ $w\n";
        }
    }
    echo "=================================================================\n";
    exit(0);
} else {
    echo " RESULTADO: SE ENCONTRARON PROBLEMAS CRÍTICOS:\n";
    foreach ($errors as $e) {
        echo "  ❌ $e\n";
    }
    echo "=================================================================\n";
    exit(1);
}
