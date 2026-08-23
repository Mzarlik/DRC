<?php
require_once '../core/Auth.php';
\Core\Auth::check();

$current_module = basename(dirname($_SERVER['SCRIPT_NAME']));
$path_prefix = ($current_module == 'public') ? '../modules/' : '../';
$db_link = ($current_module == 'public') ? 'index.php' : '../../public/index.php';
$logout_link = ($current_module == 'public') ? 'logout.php' : '../../public/logout.php';
$profile_link = ($current_module == 'public') ? 'perfil.php' : '../../public/perfil.php';
$notif_api = ($current_module == 'public') ? 'api/notifications.php' : '../../public/api/notifications.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP - Dirección de Registro Civil</title>
    <!-- Assets Locales (No CDN / Offline) -->
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
        <nav id="sidebar" class="offcanvas-lg offcanvas-start" tabindex="-1">
        <div class="sidebar-header d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-building-columns"></i> <span class="sidebar-text">ERP DRC</span></span>
            <button type="button" class="btn-close btn-close-white d-md-none" id="sidebarCloseMobile" aria-label="Close"></button>
        </div>
        <ul class="list-unstyled components">
            <li class="<?php echo ($current_module == 'public' && basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                <a href="<?php echo $db_link; ?>"><i class="fa-solid fa-chart-line"></i> <span class="sidebar-text">Dashboard</span></a>
            </li>
            
            <li class="<?php echo ($current_module == 'ciudadanos') ? 'active' : ''; ?>">
                <a href="#catSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo ($current_module == 'ciudadanos') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-address-book"></i> <span class="sidebar-text">Catálogos</span>
                </a>
                <ul class="collapse list-unstyled <?php echo ($current_module == 'ciudadanos') ? 'show' : ''; ?>" id="catSubmenu">
                    <li class="<?php echo ($current_module == 'ciudadanos') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'ciudadanos') ? 'index.php' : $path_prefix . 'ciudadanos/index.php'; ?>"><i class="fa-solid fa-users"></i> <span class="sidebar-text">Ciudadanos</span></a></li>
                </ul>
            </li>

            <!-- Registros de Actos (Oficialía) -->
            <?php if (\Core\Auth::hasPermission('permiso_registro_nacimientos') || \Core\Auth::hasPermission('permiso_registro_matrimonios') || \Core\Auth::hasPermission('permiso_registro_divorcios') || \Core\Auth::hasPermission('permiso_registro_defunciones') || \Core\Auth::hasPermission('permiso_registro_inscripciones') || \Core\Auth::hasPermission('permiso_registro_reconocimientos')): ?>
            <li class="<?php echo in_array($current_module, ['nacimientos', 'matrimonios', 'divorcios', 'defunciones', 'inscripciones', 'reconocimientos']) ? 'active' : ''; ?>">
                <a href="#vitalesSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-heart-pulse"></i> <span class="sidebar-text">Registro de Actos</span>
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($current_module, ['nacimientos', 'matrimonios', 'divorcios', 'defunciones', 'inscripciones', 'reconocimientos']) ? 'show' : ''; ?>" id="vitalesSubmenu">
                    <?php if (\Core\Auth::hasPermission('permiso_registro_nacimientos')): ?>
                    <li class="<?php echo ($current_module == 'nacimientos') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'nacimientos') ? 'index.php' : $path_prefix . 'nacimientos/index.php'; ?>"><i class="fa-solid fa-baby"></i> <span class="sidebar-text">Nacimientos</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_matrimonios')): ?>
                    <li class="<?php echo ($current_module == 'matrimonios') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'matrimonios') ? 'index.php' : $path_prefix . 'matrimonios/index.php'; ?>"><i class="fa-solid fa-ring"></i> <span class="sidebar-text">Matrimonios</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_divorcios')): ?>
                    <li class="<?php echo ($current_module == 'divorcios') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'divorcios') ? 'index.php' : $path_prefix . 'divorcios/index.php'; ?>"><i class="fa-solid fa-heart-crack"></i> <span class="sidebar-text">Divorcios</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_defunciones')): ?>
                    <li class="<?php echo ($current_module == 'defunciones') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'defunciones') ? 'index.php' : $path_prefix . 'defunciones/index.php'; ?>"><i class="fa-solid fa-book-skull"></i> <span class="sidebar-text">Defunciones</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_inscripciones')): ?>
                    <li class="<?php echo ($current_module == 'inscripciones') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'inscripciones') ? 'index.php' : $path_prefix . 'inscripciones/index.php'; ?>"><i class="fa-solid fa-passport"></i> <span class="sidebar-text">Inscripciones</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_reconocimientos')): ?>
                    <li class="<?php echo ($current_module == 'reconocimientos') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'reconocimientos') ? 'index.php' : $path_prefix . 'reconocimientos/index.php'; ?>"><i class="fa-solid fa-user-check"></i> <span class="sidebar-text">Reconocimientos</span></a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Expedición de Actas -->
            <?php if (\Core\Auth::hasPermission('permiso_actas_locales') || \Core\Auth::hasPermission('permiso_actas_foraneas')): ?>
            <li class="<?php echo in_array($current_module, ['actas_locales', 'foraneas']) ? 'active' : ''; ?>">
                <a href="#actasSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-print"></i> <span class="sidebar-text">Expedición de Actas</span>
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($current_module, ['actas_locales', 'foraneas']) ? 'show' : ''; ?>" id="actasSubmenu">
                    <?php if (\Core\Auth::hasPermission('permiso_actas_locales')): ?>
                    <li class="<?php echo ($current_module == 'actas_locales') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'actas_locales') ? 'index.php' : $path_prefix . 'actas_locales/index.php'; ?>"><i class="fa-solid fa-file-invoice"></i> <span class="sidebar-text">Actas Locales</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_actas_foraneas')): ?>
                    <li class="<?php echo ($current_module == 'foraneas') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'foraneas') ? 'index.php' : $path_prefix . 'foraneas/index.php'; ?>"><i class="fa-solid fa-plane-arrival"></i> <span class="sidebar-text">Actas Foráneas</span></a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Constancias e Inexistencias -->
            <?php if (\Core\Auth::hasPermission('permiso_constancias')): ?>
            <li class="<?php echo ($current_module == 'inexistencias') ? 'active' : ''; ?>">
                <a href="#constSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo ($current_module == 'inexistencias') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-file-signature"></i> <span class="sidebar-text">Constancias</span>
                </a>
                <ul class="collapse list-unstyled <?php echo ($current_module == 'inexistencias') ? 'show' : ''; ?>" id="constSubmenu">
                    <li class="<?php echo ($current_module == 'inexistencias') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'inexistencias') ? 'index.php' : $path_prefix . 'inexistencias/index.php'; ?>"><i class="fa-solid fa-file-circle-exclamation"></i> <span class="sidebar-text">Inexistencia / No Deudor</span></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Reportes Cruzados -->
            <li class="<?php echo ($current_module == 'reportes') ? 'active' : ''; ?>">
                <a href="<?php echo ($current_module == 'reportes') ? 'index.php' : $path_prefix . 'reportes/index.php'; ?>"><i class="fa-solid fa-file-excel"></i> <span class="sidebar-text">Reportes Cruzados</span></a>
            </li>

            <!-- Servicios CURP -->
            <?php if (\Core\Auth::hasPermission('permiso_curp')): ?>
            <li class="<?php echo ($current_module == 'curp') ? 'active' : ''; ?>">
                <a href="#curpSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo ($current_module == 'curp') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-id-card"></i> <span class="sidebar-text">Servicios CURP</span>
                </a>
                <ul class="collapse list-unstyled <?php echo ($current_module == 'curp') ? 'show' : ''; ?>" id="curpSubmenu">
                    <li class="<?php echo ($current_module == 'curp') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'curp') ? 'index.php' : $path_prefix . 'curp/index.php'; ?>"><i class="fa-solid fa-fingerprint"></i> <span class="sidebar-text">Trámites CURP</span></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Ventanilla y Seguimiento -->
            <?php if (\Core\Auth::hasPermission('permiso_peticiones_rapidas') || \Core\Auth::hasPermission('permiso_tickets')): ?>
            <li class="<?php echo in_array($current_module, ['peticion_rapida', 'peticiones']) ? 'active' : ''; ?>">
                <a href="#ventanillaSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array($current_module, ['peticion_rapida', 'peticiones']) ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-person-shelter"></i> <span class="sidebar-text">Ventanilla</span>
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($current_module, ['peticion_rapida', 'peticiones']) ? 'show' : ''; ?>" id="ventanillaSubmenu">
                    <?php if (\Core\Auth::hasPermission('permiso_peticiones_rapidas')): ?>
                    <li class="<?php echo ($current_module == 'peticion_rapida' && basename($_SERVER['PHP_SELF']) == 'create.php') ? 'active' : ''; ?>">
                        <a href="<?php echo ($current_module == 'peticion_rapida') ? 'create.php' : $path_prefix . 'peticion_rapida/create.php'; ?>">
                            <i class="fa-solid fa-bolt text-warning"></i> <span class="sidebar-text">Petición Rápida</span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_module == 'peticion_rapida' && (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'edit.php')) ? 'active' : ''; ?>">
                        <a href="<?php echo ($current_module == 'peticion_rapida') ? 'index.php' : $path_prefix . 'peticion_rapida/index.php'; ?>">
                            <i class="fa-solid fa-table-list text-primary"></i> <span class="sidebar-text">Control de Peticiones</span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_module == 'peticion_rapida' && basename($_SERVER['PHP_SELF']) == 'reporte_diario.php') ? 'active' : ''; ?>">
                        <a href="<?php echo ($current_module == 'peticion_rapida') ? 'reporte_diario.php' : $path_prefix . 'peticion_rapida/reporte_diario.php'; ?>">
                            <i class="fa-solid fa-file-invoice text-info"></i> <span class="sidebar-text">Reporte Diario</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_tickets')): ?>
                    <li class="<?php echo ($current_module == 'peticiones') ? 'active' : ''; ?>">
                        <a href="<?php echo ($current_module == 'peticiones') ? 'index.php' : $path_prefix . 'peticiones/index.php'; ?>">
                            <i class="fa-solid fa-folder-open text-success"></i> <span class="sidebar-text">Ventanilla de Seguimiento</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Administración (Admin / Supervisor) -->
            <?php if (in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'COORDINADOR', 'SUPERVISOR'])): ?>
            <li class="<?php echo ($current_module == 'public' && (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php')) ? 'active' : ''; ?>">
                <a href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-users-gear"></i> <span class="sidebar-text">Administración</span>
                </a>
                <ul class="collapse list-unstyled <?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'show' : ''; ?>" id="adminSubmenu">
                    <?php if (($_SESSION['user_rol'] ?? '') === 'ADMIN'): ?>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'public') ? 'usuarios.php' : '../../public/usuarios.php'; ?>"><i class="fa-solid fa-user-shield"></i> <span class="sidebar-text">Usuarios y Permisos</span></a></li>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'public') ? 'auditoria.php' : '../../public/auditoria.php'; ?>"><i class="fa-solid fa-clipboard-list"></i> <span class="sidebar-text">Auditoría y Errores</span></a></li>
                    <?php endif; ?>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'public') ? 'catalogos.php' : '../../public/catalogos.php'; ?>"><i class="fa-solid fa-gears"></i> <span class="sidebar-text">Conceptos y Catálogos</span></a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-flex align-items-center ms-auto">
                    <!-- Historial de Notificaciones -->
                    <div class="dropdown me-3" id="notificacionesMenu">
                        <a class="nav-link dropdown-toggle text-dark position-relative no-caret p-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Centro de Notificaciones">
                            <i class="fa-solid fa-bell fa-lg"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="font-size: 0.65rem; display: none;">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end py-0 shadow border-0 notif-dropdown" style="width: 350px; max-height: 420px; overflow-y: auto;" id="notifList">
                            <li class="p-3 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><i class="fa-solid fa-bell me-1 text-primary"></i> Notificaciones</span>
                                    <span class="badge bg-primary rounded-pill" id="notifTotal">0</span>
                                </div>
                            </li>
                            <li class="p-4 text-center text-muted" id="notifEmpty">
                                <i class="fa-solid fa-bell-slash mb-2 fa-2x text-muted opacity-50"></i>
                                <p class="mb-0 small fw-semibold">Sin notificaciones pendientes</p>
                            </li>
                        </ul>
                    </div>

                    <!-- Perfil de Usuario -->
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo \Core\Utils::getAvatarHtml(\Core\Auth::getUserName(), 34); ?>
                            <span class="fw-semibold ms-1"><?php echo htmlspecialchars(\Core\Auth::getUserName()); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item py-2" href="<?php echo $profile_link; ?>"><i class="fa-solid fa-user fa-sm me-2 text-muted"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $logout_link; ?>"><i class="fa-solid fa-right-from-bracket fa-sm me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Dashboard Interactivo</h2>
                    <p class="text-muted small mb-0">Resumen operativo y estadísticas en tiempo real</p>
                </div>
                <div>
                    <span class="badge bg-light text-dark border px-3 py-2 fw-normal">
                        <i class="fa-regular fa-calendar me-1"></i> <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
            </div>
            
            <!-- 5 Tarjetas KPI Superiores con Luxury Gradients -->
            <section aria-label="Indicadores clave de rendimiento" class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
                <div class="col">
                    <div class="card-kpi kpi-burgundy" role="region" aria-label="Trámites Hoy">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">Trámites Hoy</div>
                                <div class="kpi-value" id="card-hoy" aria-live="polite">0</div>
                            </div>
                            <div class="kpi-icon-badge" aria-hidden="true"><i class="fa-solid fa-calendar-day"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card-kpi kpi-slate" role="region" aria-label="Tickets Pendientes">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">Tickets Pendientes</div>
                                <div class="kpi-value" id="card-peticiones" aria-live="polite">0</div>
                            </div>
                            <div class="kpi-icon-badge" aria-hidden="true"><i class="fa-solid fa-ticket"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card-kpi kpi-gold" role="region" aria-label="Inexistencias">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">Inexistencias</div>
                                <div class="kpi-value" id="card-inexistencias" aria-live="polite">0</div>
                            </div>
                            <div class="kpi-icon-badge" aria-hidden="true"><i class="fa-solid fa-clock"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card-kpi kpi-emerald" role="region" aria-label="Foráneas Validadas">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">Foráneas Validadas</div>
                                <div class="kpi-value" id="card-foraneas" aria-live="polite">0</div>
                            </div>
                            <div class="kpi-icon-badge" aria-hidden="true"><i class="fa-solid fa-check-double"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card-kpi kpi-teal" role="region" aria-label="Recaudación">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="kpi-label">Recaudación</div>
                                <div class="kpi-value" id="card-recaudacion" aria-live="polite">$0.00</div>
                            </div>
                            <div class="kpi-icon-badge" aria-hidden="true"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                        </div>
                    </div>
                </div>
            </section>
            
            <div class="row mb-4">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-chart-line text-primary me-2"></i> Tendencia de Trámites Procesados (Últimos 7 Días)</span>
                        </div>
                        <div class="card-body">
                            <canvas id="diarioChart" role="img" aria-label="Gráfica lineal de tendencia de trámites procesados en los últimos 7 días" style="max-height: 320px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fa-solid fa-bolt text-primary me-2"></i> Accesos Rápidos
                        </div>
                        <div class="card-body">
                            <a href="../modules/peticion_rapida/create.php" class="quick-action-btn">
                                <i class="fa-solid fa-bolt text-warning"></i>
                                <span><strong>Petición Rápida</strong> (Captura)</span>
                            </a>
                            <a href="../modules/peticion_rapida/index.php" class="quick-action-btn">
                                <i class="fa-solid fa-table-list text-primary"></i>
                                <span>Control de Peticiones</span>
                            </a>
                            <a href="../modules/peticion_rapida/reporte_diario.php" class="quick-action-btn">
                                <i class="fa-solid fa-file-invoice text-info"></i>
                                <span>Reporte Diario Oficial</span>
                            </a>
                            <a href="../modules/peticiones/index.php" class="quick-action-btn">
                                <i class="fa-solid fa-folder-open text-emerald"></i>
                                <span>Ventanilla de Seguimiento</span>
                            </a>
                            <a href="../modules/nacimientos/create.php" class="quick-action-btn">
                                <i class="fa-solid fa-baby text-primary"></i>
                                <span>Registrar Nacimiento</span>
                            </a>
                            <a href="../modules/foraneas/create.php" class="quick-action-btn mb-0">
                                <i class="fa-solid fa-plane-arrival text-slate"></i>
                                <span>Registrar Acta Foránea</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila de Gráficas: Recaudación Proyectada y Carga Operativa -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fa-solid fa-coins text-emerald me-2"></i> Recaudación Proyectada por Acto
                        </div>
                        <div class="card-body">
                            <canvas id="recaudacionChart" role="img" aria-label="Gráfica de barras con la recaudación proyectada por cada acto registral" style="max-height: 280px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fa-solid fa-chart-pie text-slate me-2"></i> Carga Operativa por Módulo
                        </div>
                        <div class="card-body">
                            <canvas id="cargaChart" role="img" aria-label="Gráfica de dona con la distribución de carga operativa entre módulos" style="max-height: 280px; width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/chartjs/chart.umd.min.js"></script>
<script src="../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        $.ajax({
            url: 'api/stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Update Top Cards
                    $('#card-hoy').text(response.cards.tramites_hoy || 0);
                    $('#card-peticiones').text(response.cards.peticiones_pendientes || 0);
                    $('#card-inexistencias').text(response.cards.inexistencias_proceso || 0);
                    $('#card-foraneas').text(response.cards.foraneas_validadas || 0);
                    $('#card-recaudacion').text('$' + (parseFloat(response.cards.recaudacion_estimada || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})));

                    // Theme-aware Chart Settings
                    const isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
                    const textColor = isDark ? '#94A3B8' : '#64748B';
                    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
                    const donutBorder = isDark ? '#1E293B' : '#FFFFFF';

                    // 1. Gráfica de Tendencia (Line Chart Guinda/Vino)
                    const ctxDiario = document.getElementById('diarioChart').getContext('2d');
                    const gradDiario = ctxDiario.createLinearGradient(0, 0, 0, 300);
                    gradDiario.addColorStop(0, 'rgba(105, 28, 50, 0.35)');
                    gradDiario.addColorStop(1, 'rgba(105, 28, 50, 0.00)');

                    new Chart(ctxDiario, {
                        type: 'line',
                        data: {
                            labels: response.processed_by_day.labels,
                            datasets: [{
                                label: 'Trámites',
                                data: response.processed_by_day.data,
                                borderColor: '#8C1D33',
                                borderWidth: 3,
                                backgroundColor: gradDiario,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#B38E5D',
                                pointBorderColor: donutBorder,
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                                pointRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor },
                                    grid: { color: gridColor }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1, color: textColor },
                                    grid: { color: gridColor }
                                }
                            }
                        }
                    });

                    // 2. Gráfica de Recaudación (Bar Chart Emerald/Teal)
                    const ctxRec = document.getElementById('recaudacionChart').getContext('2d');
                    const gradRec = ctxRec.createLinearGradient(0, 0, 0, 280);
                    gradRec.addColorStop(0, '#0F766E');
                    gradRec.addColorStop(1, '#14B8A6');

                    new Chart(ctxRec, {
                        type: 'bar',
                        data: {
                            labels: response.recaudacion_proyectada.labels,
                            datasets: [{
                                label: 'Monto Proyectado (MXN)',
                                data: response.recaudacion_proyectada.data,
                                backgroundColor: gradRec,
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor },
                                    grid: { color: gridColor }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: textColor,
                                        callback: function(value) {
                                            return '$' + value;
                                        }
                                    },
                                    grid: { color: gridColor }
                                }
                            }
                        }
                    });

                    // 3. Gráfica de Carga Operativa (Doughnut Chart Paleta Corporativa)
                    const ctxCarga = document.getElementById('cargaChart').getContext('2d');
                    new Chart(ctxCarga, {
                        type: 'doughnut',
                        data: {
                            labels: response.carga_operativa.labels,
                            datasets: [{
                                data: response.carga_operativa.data,
                                backgroundColor: [
                                    '#8C1D33', // Guinda
                                    '#B38E5D', // Dorado
                                    '#0F766E', // Esmeralda
                                    '#3B82F6', // Azul brillante
                                    '#BE123C', // Rosa oscuro
                                    '#D97706', // Ámbar
                                    '#0284C7', // Azul cielo
                                    '#7C3AED', // Violeta
                                    '#64748B'  // Gris
                                ],
                                borderWidth: 2,
                                borderColor: donutBorder
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 12,
                                        color: textColor,
                                        font: { size: 11 }
                                    }
                                }
                            },
                            cutout: '68%'
                        }
                    });
                }
            }
        });
    });
</script>
<script src="../assets/js/global.js"></script>
</body>
</html>
