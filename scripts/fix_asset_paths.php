<?php
// scripts/fix_asset_paths.php

$baseDir = dirname(__DIR__);

// 1. En public/*.php => "../assets/"
$publicFiles = glob($baseDir . '/public/*.php');
foreach ($publicFiles as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('#(?:\.\./)*assets/#', '../assets/', $content);
    file_put_contents($file, $content);
    echo "✔ Public: " . basename($file) . "\n";
}

// 2. En modules/*/*.php => "../../assets/"
$moduleFiles = glob($baseDir . '/modules/*/*.php');
foreach ($moduleFiles as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('#(?:\.\./)*assets/#', '../../assets/', $content);
    file_put_contents($file, $content);
    echo "✔ Module: " . basename(dirname($file)) . '/' . basename($file) . "\n";
}

echo "\nNormalización exacta completada.\n";
