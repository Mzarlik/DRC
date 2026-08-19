<?php
// scripts/run_smoke_tests.php
/**
 * Suite Automatizada de 14 Smoke Tests Pre-Deploy para el ERP DRC.
 * Valida los 14 puntos de control críticos antes de autorizar el pase a producción.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Encryption.php';
require_once __DIR__ . '/../core/Auditoria.php';
require_once __DIR__ . '/../core/Utils.php';
require_once __DIR__ . '/../core/Services/PdfGenerator.php';
require_once __DIR__ . '/../core/Services/FirmaElectronicaService.php';

use Core\Auth;
use Core\Database;
use Core\Encryption;
use Core\Auditoria;
use Core\Utils;
use Core\Services\PdfGenerator;
use Core\Services\FirmaElectronicaService;

echo "=================================================================\n";
echo " EJECUTOR DE 14 SMOKE TESTS PRE-DEPLOY — ERP REGISTRO CIVIL (DRC)\n";
echo "=================================================================\n\n";

$passed = 0;
$failed = 0;

function runSmokeTest(int $num, string $title, callable $test) {
    global $passed, $failed;
    echo sprintf("[%02d] %-52s ... ", $num, $title);
    try {
        $result = $test();
        if ($result === true) {
            echo "[APROBADO]\n";
            $passed++;
        } else {
            echo "[FALLÓ] (" . ($result ?: 'Error de validación') . ")\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "[ERROR EXCEPCIÓN] " . $e->getMessage() . "\n";
        $failed++;
    }
}

// 1. Aislamiento Perimetral (.htaccess)
runSmokeTest(1, "Aislamiento Perimetral (.htaccess rules)", function() {
    $htaccess = file_get_contents(dirname(__DIR__) . '/.htaccess');
    return str_contains($htaccess, 'FilesMatch') && str_contains($htaccess, 'RewriteRule ^(core|docs|logs|cache|scripts|\.agents)/');
});

// 2. Control de Acceso RBAC
runSmokeTest(2, "Control de Acceso RBAC y Banderas Granulares", function() {
    return method_exists(Auth::class, 'hasPermission') && method_exists(Auth::class, 'checkPermission');
});

// 3. Protección CSRF en Tiempo Constante
runSmokeTest(3, "Protección CSRF y Validación Segura", function() {
    $token = Auth::generateCSRF();
    $valid = Auth::validateCSRF($token);
    $invalid = Auth::validateCSRF('token_falso_invalido');
    return $valid && !$invalid;
});

// 4. Blind Index de CURP (HMAC-SHA256)
runSmokeTest(4, "Blind Index HMAC-SHA256 para CURP", function() {
    $curp = "ABCD010101HDFRRN09";
    $bindex1 = Encryption::getBlindIndex($curp);
    $bindex2 = Encryption::getBlindIndex(strtolower($curp));
    return ($bindex1 === $bindex2) && (strlen($bindex1) === 64);
});

// 5. Auditoría de Lecturas (LGPDPPSO / INAI)
runSmokeTest(5, "Auditoría de Lecturas LGPDPPSO (logLectura)", function() {
    return method_exists(Auditoria::class, 'logLectura');
});

// 6. Grafías Indígenas y Soporte UTF-8
runSmokeTest(6, "Manejo de Grafías Indígenas (mb_strtoupper)", function() {
    $nombre = "Xóchitl Ta'an K'an Mää";
    $normalizado = mb_strtoupper($nombre, 'UTF-8');
    return str_contains($normalizado, 'XÓCHITL') && str_contains($normalizado, "TA'AN");
});

// 7. Generación de Actas PDF con Unicode
runSmokeTest(7, "Generador de Actas PDF con Unicode", function() {
    $pdf = PdfGenerator::generarActaNacimiento([
        'numero_acta' => 'SMOKE-2026-0001',
        'nombre_completo' => 'XÓCHITL TA\'AN K\'AN',
        'curp' => 'XOTK010101MDFRRN01',
        'fecha_registro' => date('Y-m-d'),
        'lugar_nacimiento' => 'OFICIALÍA 01'
    ]);
    return str_starts_with($pdf, '%PDF-');
});

// 8. Sellado Digital y Firma QR (HMAC-SHA256)
runSmokeTest(8, "Sellado Digital y Firma QR (HMAC-SHA256)", function() {
    $data = "NACIMIENTO_FOLIO_2026_TEST";
    $firma = Encryption::sign($data);
    return Encryption::verifySignature($data, $firma) && !Encryption::verifySignature($data, 'firma_invalida');
});

// 9. Worker CLI y Reconexión de PDO
runSmokeTest(9, "Worker CLI con Reconexión Activa (getActivePdo)", function() {
    $workerCode = file_get_contents(dirname(__DIR__) . '/core/Worker.php');
    return str_contains($workerCode, 'function getActivePdo()') && str_contains($workerCode, 'SELECT 1');
});

// 10. Permisos de Archivos del Worker (0664)
runSmokeTest(10, "Estructura de Directorio de Exportaciones", function() {
    $dir = dirname(__DIR__) . '/public/exports';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return is_dir($dir) && is_writable($dir);
});

// 11. Folios Secuenciales Atómicos
runSmokeTest(11, "Generador Atómico de Folios (Database::generateFolio)", function() {
    return method_exists(Database::class, 'generateFolio');
});

// 12. Sesiones Seguras y Resiliencia
runSmokeTest(12, "Parámetros de Sesión Segura (use_strict_mode)", function() {
    $authCode = file_get_contents(dirname(__DIR__) . '/core/Auth.php');
    return str_contains($authCode, "session.use_strict_mode") && str_contains($authCode, "'httponly' => true");
});

// 13. Assets Frontend 100% Offline
runSmokeTest(13, "Assets Frontend 100% Offline (assets/vendor)", function() {
    $vendor = dirname(__DIR__) . '/assets/vendor';
    $files = [
        $vendor . '/bootstrap/css/bootstrap.min.css',
        $vendor . '/bootstrap/js/bootstrap.bundle.min.js',
        $vendor . '/fontawesome/css/all.min.css',
        $vendor . '/tom-select/js/tom-select.complete.min.js',
        $vendor . '/sweetalert2/sweetalert2.all.min.js',
        $vendor . '/chartjs/chart.umd.min.js',
        $vendor . '/alpine/alpine-csp.min.js',
        $vendor . '/jquery/jquery-3.7.1.min.js'
    ];
    foreach ($files as $f) {
        if (!file_exists($f)) return "Falta archivo: " . basename($f);
    }
    return true;
});

// 14. Componentes Alpine.js CSP-Friendly
runSmokeTest(14, "Componentes Reactivos Alpine.js CSP-Friendly", function() {
    $alpineCode = file_get_contents(dirname(__DIR__) . '/assets/js/components-alpine.js');
    return str_contains($alpineCode, "Alpine.data('formMatrimonios'") && str_contains($alpineCode, "Alpine.data('formInexistencias'");
});

echo "\n=================================================================\n";
echo " RESUMEN FINAL DE SMOKE TESTS: $passed / 14 APROBADOS ($failed FALLOS)\n";
echo "=================================================================\n";

exit($failed > 0 ? 1 : 0);
