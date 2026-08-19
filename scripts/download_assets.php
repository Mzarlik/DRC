<?php
// scripts/download_assets.php
/**
 * Script multiplataforma para descargar y organizar paquetes frontend locales
 * en assets/vendor/ para el ERP DRC (Operatividad 100% Offline e Intranet).
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

echo "=================================================================\n";
echo " DESCARGADOR DE ASSETS FRONTEND LOCALES — ERP DRC (OFFLINE)     \n";
echo "=================================================================\n\n";

$vendorDir = dirname(__DIR__) . '/assets/vendor';

$assets = [
    // 1. Bootstrap 5.3.2
    'bootstrap/css/bootstrap.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'bootstrap/js/bootstrap.bundle.min.js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',

    // 2. FontAwesome 6.4.2
    'fontawesome/css/all.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
    'fontawesome/webfonts/fa-solid-900.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/webfonts/fa-solid-900.woff2',
    'fontawesome/webfonts/fa-regular-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/webfonts/fa-regular-400.woff2',
    'fontawesome/webfonts/fa-brands-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/webfonts/fa-brands-400.woff2',
    'fontawesome/webfonts/fa-solid-900.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/webfonts/fa-solid-900.ttf',
    'fontawesome/webfonts/fa-regular-400.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/webfonts/fa-regular-400.ttf',

    // 3. DataTables 1.13.7 & Responsive 2.5.0
    'datatables/css/dataTables.bootstrap5.min.css' => 'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css',
    'datatables/css/responsive.bootstrap5.min.css' => 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css',
    'datatables/js/jquery.dataTables.min.js' => 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'datatables/js/dataTables.bootstrap5.min.js' => 'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
    'datatables/js/dataTables.responsive.min.js' => 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
    'datatables/js/responsive.bootstrap5.min.js' => 'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',

    // 4. TomSelect 2.3.1
    'tom-select/css/tom-select.bootstrap5.min.css' => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css',
    'tom-select/js/tom-select.complete.min.js' => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',

    // 5. SweetAlert2 11.10.0
    'sweetalert2/sweetalert2.min.css' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css',
    'sweetalert2/sweetalert2.all.min.js' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js',

    // 6. Chart.js 4.4.1
    'chartjs/chart.umd.min.js' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',

    // 7. Alpine.js CSP-Friendly (@alpinejs/csp)
    'alpine/alpine-csp.min.js' => 'https://cdn.jsdelivr.net/npm/@alpinejs/csp@3.13.5/dist/cdn.min.js',

    // 8. jQuery 3.7.1 Local (para DataTables)
    'jquery/jquery-3.7.1.min.js' => 'https://code.jquery.com/jquery-3.7.1.min.js'
];

$successCount = 0;
$failCount = 0;

foreach ($assets as $relPath => $url) {
    $targetFile = $vendorDir . '/' . $relPath;
    $targetDir = dirname($targetFile);

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    echo "Descargando $relPath ... ";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DRC/1.4');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $content !== false && strlen($content) > 0) {
        file_put_contents($targetFile, $content);
        $sizeKb = round(strlen($content) / 1024, 1);
        echo "[OK] ({$sizeKb} KB)\n";
        $successCount++;
    } else {
        echo "[ERROR] HTTP $httpCode\n";
        $failCount++;
    }
}

echo "\n=================================================================\n";
echo " RESUMEN: $successCount archivos descargados exitosamente, $failCount fallos.\n";
echo " Ubicación: assets/vendor/\n";
echo "=================================================================\n";

exit($failCount > 0 ? 1 : 0);
