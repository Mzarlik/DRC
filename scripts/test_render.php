<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_nombre'] = 'ADMINISTRADOR GENERAL';
$_SESSION['user_rol'] = 'ADMIN';
$_SESSION['permiso_exportar'] = 1;
$_SESSION['permiso_registro_nacimientos'] = 1;
$_SESSION['permiso_registro_matrimonios'] = 1;
$_SESSION['permiso_registro_divorcios'] = 1;
$_SESSION['permiso_registro_defunciones'] = 1;
$_SESSION['permiso_registro_inscripciones'] = 1;
$_SESSION['permiso_registro_reconocimientos'] = 1;
$_SESSION['permiso_actas_locales'] = 1;
$_SESSION['permiso_actas_foraneas'] = 1;
$_SESSION['permiso_constancias'] = 1;
$_SESSION['permiso_peticiones_rapidas'] = 1;
$_SESSION['permiso_turnos'] = 1;
$_SERVER['SCRIPT_NAME'] = '/DRC/public/index.php';
$_SERVER['PHP_SELF'] = '/DRC/public/index.php';
$_SERVER['REQUEST_URI'] = '/DRC/public/index.php';

chdir(__DIR__ . '/../public');
ob_start();
include 'index.php';
$html = ob_get_clean();
echo "HTML Length: " . strlen($html) . "\n";
echo "HTML Snippet (first 500 chars):\n" . substr($html, 0, 500) . "\n...\n";
echo "HTML Snippet (around line 200):\n" . substr($html, 2000, 1000) . "\n";
