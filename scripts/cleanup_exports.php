<?php
// scripts/cleanup_exports.php
/**
 * Script de mantenimiento programado para purgar archivos temporales y reportes antiguos.
 * Diseñado para ser ejecutado vía Cron diario (/etc/cron.daily/drc-cleanup)
 * o Programador de Tareas en Windows.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

echo "=================================================================\n";
echo " PURGA Y LIMPIEZA DE TEMPORALES — ERP DIRECCIÓN DE REGISTRO CIVIL\n";
echo "=================================================================\n\n";

$baseDir = dirname(__DIR__);
$now = time();
$deletedCount = 0;

$cleanupRules = [
    [
        'dir' => $baseDir . '/public/exports',
        'pattern' => '*.xlsx',
        'max_age_seconds' => 48 * 3600, // 48 Horas
        'label' => 'Reportes Excel de exportación'
    ],
    [
        'dir' => $baseDir . '/public/reports',
        'pattern' => '*.pdf',
        'max_age_seconds' => 7 * 24 * 3600, // 7 Días
        'label' => 'Actas y constancias PDF temporales'
    ],
    [
        'dir' => $baseDir . '/logs',
        'pattern' => '*.log',
        'max_age_seconds' => 30 * 24 * 3600, // 30 Días
        'label' => 'Logs antiguos locales'
    ]
];

foreach ($cleanupRules as $rule) {
    echo "• Inspeccionando {$rule['label']} ({$rule['pattern']}) ... ";
    if (!is_dir($rule['dir'])) {
        echo "[DIRECTORIO NO EXISTE]\n";
        continue;
    }

    $files = glob($rule['dir'] . '/' . $rule['pattern']);
    $purgedInDir = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            $age = $now - filemtime($file);
            if ($age > $rule['max_age_seconds']) {
                if (@unlink($file)) {
                    $purgedInDir++;
                    $deletedCount++;
                }
            }
        }
    }

    echo "[$purgedInDir archivos eliminados]\n";
}

echo "\n=================================================================\n";
echo " RESUMEN: $deletedCount archivos temporales purgados exitosamente.\n";
echo "=================================================================\n";
