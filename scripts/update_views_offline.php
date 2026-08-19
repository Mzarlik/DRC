<?php
// scripts/update_views_offline.php
/**
 * Script automatizado para migrar todas las referencias CDN en public/ y modules/
 * a los paquetes locales en assets/vendor/ y avatares locales para soporte 100% offline.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

$baseDir = dirname(__DIR__);
$files = array_merge(
    glob($baseDir . '/public/*.php'),
    glob($baseDir . '/modules/*/*.php')
);

$updatedCount = 0;

foreach ($files as $file) {
    // Determinar nivel de profundidad relativo a assets/
    $relPath = str_replace($baseDir, '', $file);
    $depth = substr_count(trim($relPath, '/\\'), DIRECTORY_SEPARATOR);
    $vendorPrefix = ($depth >= 2) ? '../../assets/vendor' : '../assets/vendor';

    $content = file_get_contents($file);
    $original = $content;

    // 1. Reemplazo de CSS
    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/bootstrap@5\.[0-9.]+/dist/css/bootstrap\.min\.css#',
        $vendorPrefix . '/bootstrap/css/bootstrap.min.css',
        $content
    );

    $content = preg_replace(
        '#https://cdnjs\.cloudflare\.com/ajax/libs/font-awesome/[0-9.]+/css/all\.min\.css#',
        $vendorPrefix . '/fontawesome/css/all.min.css',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/sweetalert2@[0-9.]+/dist/sweetalert2\.min\.css#',
        $vendorPrefix . '/sweetalert2/sweetalert2.min.css',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.datatables\.net/1\.[0-9.]+/css/dataTables\.bootstrap5\.min\.css#',
        $vendorPrefix . '/datatables/css/dataTables.bootstrap5.min.css',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.datatables\.net/responsive/[0-9.]+/css/responsive\.bootstrap5\.min\.css#',
        $vendorPrefix . '/datatables/css/responsive.bootstrap5.min.css',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/tom-select@[0-9.]+/dist/css/tom-select\.bootstrap5\.min\.css#',
        $vendorPrefix . '/tom-select/css/tom-select.bootstrap5.min.css',
        $content
    );

    // 2. Reemplazo de JS
    $content = preg_replace(
        '#https://code\.jquery\.com/jquery-3\.[0-9.]+\.min\.js#',
        $vendorPrefix . '/jquery/jquery-3.7.1.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/bootstrap@5\.[0-9.]+/dist/js/bootstrap\.bundle\.min\.js#',
        $vendorPrefix . '/bootstrap/js/bootstrap.bundle.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/sweetalert2@[0-9.]+/dist/sweetalert2\.all\.min\.js#',
        $vendorPrefix . '/sweetalert2/sweetalert2.all.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.datatables\.net/1\.[0-9.]+/js/jquery\.dataTables\.min\.js#',
        $vendorPrefix . '/datatables/js/jquery.dataTables.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.datatables\.net/1\.[0-9.]+/js/dataTables\.bootstrap5\.min\.js#',
        $vendorPrefix . '/datatables/js/dataTables.bootstrap5.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.datatables\.net/responsive/[0-9.]+/js/dataTables\.responsive\.min\.js#',
        $vendorPrefix . '/datatables/js/dataTables.responsive.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.datatables\.net/responsive/[0-9.]+/js/responsive\.bootstrap5\.min\.js#',
        $vendorPrefix . '/datatables/js/responsive.bootstrap5.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/tom-select@[0-9.]+/dist/js/tom-select\.complete\.min\.js#',
        $vendorPrefix . '/tom-select/js/tom-select.complete.min.js',
        $content
    );

    $content = preg_replace(
        '#https://cdn\.jsdelivr\.net/npm/chart\.js#',
        $vendorPrefix . '/chartjs/chart.umd.min.js',
        $content
    );

    // 3. Reemplazo de Avatares Externos por Helper 100% Offline
    $content = preg_replace(
        '#<img src="https://ui-avatars\.com/api/\?name=<\?php echo urlencode\(\\\\Core\\\\Auth::getUserName\(\)\); \?>[^"]*" class="rounded-circle me-2" width="32" height="32" alt="User">#',
        '<?php echo \\Core\\Utils::getAvatarHtml(\\Core\\Auth::getUserName(), 32); ?>',
        $content
    );

    $content = preg_replace(
        '#<img src="https://ui-avatars\.com/api/\?name=<\?php echo urlencode\(\$user\[\'nombre\'\]\); \?>[^"]*" class="rounded-circle mb-3 shadow-sm" alt="User Avatar">#',
        '<?php echo \\Core\\Utils::getAvatarHtml($user[\'nombre\'], 80); ?>',
        $content
    );

    if ($content !== $original) {
        file_put_contents($file, $content);
        $updatedCount++;
        echo "Actualizado a assets locales: " . str_replace($baseDir, '', $file) . "\n";
    }
}

echo "\nTotal de archivos de vistas actualizados: $updatedCount\n";
