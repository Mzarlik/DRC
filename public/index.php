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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
        <!-- Sidebar -->
        <!-- Sidebar -->
        <!-- Sidebar -->
    <nav id="sidebar">
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

            <!-- Mesa de Ayuda -->
            <?php if (\Core\Auth::hasPermission('permiso_tickets')): ?>
            <li class="<?php echo ($current_module == 'peticiones') ? 'active' : ''; ?>">
                <a href="#ayudaSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo ($current_module == 'peticiones') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-headset"></i> <span class="sidebar-text">Mesa de Ayuda</span>
                </a>
                <ul class="collapse list-unstyled <?php echo ($current_module == 'peticiones') ? 'show' : ''; ?>" id="ayudaSubmenu">
                    <li class="<?php echo ($current_module == 'peticiones') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'peticiones') ? 'index.php' : $path_prefix . 'peticiones/index.php'; ?>"><i class="fa-solid fa-ticket"></i> <span class="sidebar-text">Tickets / Peticiones</span></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Administración (Admin Only) -->
            <?php if (($_SESSION['user_rol'] ?? '') === 'ADMIN'): ?>
            <li class="<?php echo ($current_module == 'public' && basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>">
                <a href="<?php echo ($current_module == 'public') ? 'usuarios.php' : '../../public/usuarios.php'; ?>"><i class="fa-solid fa-users-gear"></i> <span class="sidebar-text">Administración</span></a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
                <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary" style="background: var(--primary-color); border: none;">
                    <i class="fas fa-bars"></i>
                </button>
                                <div class="d-flex align-items-center ms-auto">
                    <!-- Historial de Notificaciones -->
                    <div class="dropdown me-3" id="notificacionesMenu">
                        <a class="nav-link dropdown-toggle text-dark position-relative no-caret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bell fa-lg"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="font-size: 0.65rem; display: none;">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end py-0 shadow border-0" style="width: 320px; max-height: 400px; overflow-y: auto;" id="notifList">
                            <li class="p-3 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Historial de Notificaciones</span>
                                    <span class="badge bg-primary rounded-pill" id="notifTotal">0</span>
                                </div>
                            </li>
                            <li class="p-3 text-center text-muted" id="notifEmpty">
                                <i class="fa-solid fa-bell-slash mb-2 fa-lg"></i>
                                <p class="mb-0 small">No hay notificaciones recientes</p>
                            </li>
                        </ul>
                    </div>

                    <!-- Perfil de Usuario -->
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(\Core\Auth::getUserName()); ?>&background=18bc9c&color=fff" class="rounded-circle me-2" width="32" height="32" alt="User">
                            <?php echo htmlspecialchars(\Core\Auth::getUserName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $profile_link; ?>"><i class="fa-solid fa-user fa-sm me-2"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $logout_link; ?>"><i class="fa-solid fa-right-from-bracket fa-sm me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <h2 class="mb-4">Dashboard Interactivo</h2>
            
            <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-4 mb-4">
                <div class="col">
                    <div class="card bg-primary text-white h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 text-white-50" style="font-size: 0.75rem;">Trámites Hoy</h6>
                                    <h2 class="mb-0 fw-bold" id="card-hoy"><span class="skeleton" style="width: 60px; height: 32px;"></span></h2>
                                </div>
                                <i class="fa-solid fa-calendar-day fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-danger text-white h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 text-white-50" style="font-size: 0.75rem;">Tickets Pendientes</h6>
                                    <h2 class="mb-0 fw-bold" id="card-peticiones"><span class="skeleton" style="width: 60px; height: 32px;"></span></h2>
                                </div>
                                <i class="fa-solid fa-ticket fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-warning text-dark h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 text-dark-50" style="font-size: 0.75rem; color: rgba(0,0,0,0.5);">Inexistencias Pendientes</h6>
                                    <h2 class="mb-0 fw-bold" id="card-inexistencias"><span class="skeleton" style="width: 60px; height: 32px;"></span></h2>
                                </div>
                                <i class="fa-solid fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-success text-white h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 text-white-50" style="font-size: 0.75rem;">Foráneas Validadas</h6>
                                    <h2 class="mb-0 fw-bold" id="card-foraneas"><span class="skeleton" style="width: 60px; height: 32px;"></span></h2>
                                </div>
                                <i class="fa-solid fa-check-double fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #18bc9c, #1abc9c);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 text-white-50" style="font-size: 0.75rem;">Recaudación Proyectada</h6>
                                    <h2 class="mb-0 fw-bold" id="card-recaudacion"><span class="skeleton" style="width: 120px; height: 32px;"></span></h2>
                                </div>
                                <i class="fa-solid fa-money-bill-trend-up fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3 border-0">
                            <i class="fa-solid fa-chart-line text-primary me-2"></i> Tendencia de Trámites Procesados (Últimos 7 Días)
                        </div>
                        <div class="card-body">
                            <div id="diarioChartSkeleton" class="skeleton skeleton-chart" style="height: 320px; width: 100%;"></div>
                            <canvas id="diarioChart" style="max-height: 350px; display: none;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3 border-0">
                            <i class="fa-solid fa-circle-nodes text-primary me-2"></i> Accesos Rápidos
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="../modules/nacimientos/create.php" class="btn btn-outline-primary py-2 text-start"><i class="fa-solid fa-baby me-2"></i> Nuevo Nacimiento</a>
                                <a href="../modules/defunciones/create.php" class="btn btn-outline-danger py-2 text-start"><i class="fa-solid fa-book-skull me-2"></i> Nueva Defunción</a>
                                <a href="../modules/peticiones/create.php" class="btn btn-outline-dark py-2 text-start"><i class="fa-solid fa-ticket me-2"></i> Abrir Ticket</a>
                                <a href="../modules/ciudadanos/create.php" class="btn btn-outline-success py-2 text-start"><i class="fa-solid fa-user-plus me-2"></i> Registrar Ciudadano</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3 border-0">
                            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Recaudación Proyectada por Módulo (MXN)
                        </div>
                        <div class="card-body">
                            <div id="recaudacionChartSkeleton" class="skeleton skeleton-chart" style="height: 320px; width: 100%;"></div>
                            <canvas id="recaudacionChart" style="max-height: 350px; display: none;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3 border-0">
                            <i class="fa-solid fa-chart-pie text-primary me-2"></i> Distribución de Carga Operativa
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center" style="min-height: 320px;">
                            <div id="cargaChartSkeleton" class="skeleton" style="width: 250px; height: 250px; border-radius: 50%;"></div>
                            <canvas id="cargaChart" style="max-height: 350px; display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        // Cargar Notificaciones
        function cargarNotificaciones() {
            $.ajax({
                url: '<?php echo $notif_api; ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.notifications.length > 0) {
                        $('#notifBadge').text(response.notifications.length).show();
                        $('#notifTotal').text(response.notifications.length);
                        $('#notifEmpty').hide();
                        
                        // Clear dynamic items
                        $('#notifList li.notif-item').remove();
                        
                        response.notifications.forEach(function(notif) {
                            let itemHtml = `
                                <li class="notif-item border-bottom">
                                    <a class="dropdown-item p-3 d-flex align-items-start" href="#" style="white-space: normal;">
                                        <div class="me-3 mt-1">
                                            <i class="fa-solid ${notif.icon} ${notif.color} fa-lg"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold small text-dark">${notif.title}</h6>
                                            <p class="mb-1 text-muted small" style="line-height: 1.3;">${notif.desc}</p>
                                            <small class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem;">${notif.time}</small>
                                        </div>
                                    </a>
                                </li>
                            `;
                            $('#notifList').append(itemHtml);
                        });
                    } else {
                        $('#notifBadge').hide();
                        $('#notifTotal').text('0');
                        $('#notifEmpty').show();
                    }
                }
            });
        }
        
        cargarNotificaciones();
        setInterval(cargarNotificaciones, 60000);

        // Cargar Estadísticas Dinámicas
        $.ajax({
            url: 'api/stats.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    // Remove skeletons and show canvases
                    $('#diarioChartSkeleton').remove();
                    $('#diarioChart').show();
                    $('#recaudacionChartSkeleton').remove();
                    $('#recaudacionChart').show();
                    $('#cargaChartSkeleton').remove();
                    $('#cargaChart').show();

                    // Update Cards
                    $('#card-hoy').text(response.cards.tramites_hoy);
                    $('#card-peticiones').text(response.cards.peticiones_pendientes);
                    $('#card-inexistencias').text(response.cards.inexistencias_pendientes);
                    $('#card-foraneas').text(response.cards.foraneas_validadas);
                    
                    // Format money for card
                    const formatter = new Intl.NumberFormat('es-MX', {
                        style: 'currency',
                        currency: 'MXN'
                    });
                    $('#card-recaudacion').text(formatter.format(response.cards.recaudacion_total));

                    // 1. Gráfica de Tendencia Diaria (Line Chart)
                    const ctxDiario = document.getElementById('diarioChart').getContext('2d');
                    const gradDiario = ctxDiario.createLinearGradient(0, 0, 0, 300);
                    gradDiario.addColorStop(0, 'rgba(44, 62, 80, 0.4)');
                    gradDiario.addColorStop(1, 'rgba(44, 62, 80, 0.01)');
                    
                    new Chart(ctxDiario, {
                        type: 'line',
                        data: {
                            labels: response.processed_by_day.labels,
                            datasets: [{
                                label: 'Trámites',
                                data: response.processed_by_day.data,
                                borderColor: '#2c3e50',
                                borderWidth: 3,
                                backgroundColor: gradDiario,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#18bc9c',
                                pointBorderColor: '#fff',
                                pointHoverRadius: 7,
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 }
                                }
                            }
                        }
                    });

                    // 2. Gráfica de Recaudación por Módulo (Bar Chart)
                    const ctxRec = document.getElementById('recaudacionChart').getContext('2d');
                    const gradRec = ctxRec.createLinearGradient(0, 0, 0, 300);
                    gradRec.addColorStop(0, '#18bc9c');
                    gradRec.addColorStop(1, '#1abc9c');

                    new Chart(ctxRec, {
                        type: 'bar',
                        data: {
                            labels: response.recaudacion_proyectada.labels,
                            datasets: [{
                                label: 'Monto Proyectado (MXN)',
                                data: response.recaudacion_proyectada.data,
                                backgroundColor: gradRec,
                                borderRadius: 5,
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
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // 3. Gráfica de Carga Operativa (Doughnut Chart)
                    const ctxCarga = document.getElementById('cargaChart').getContext('2d');
                    new Chart(ctxCarga, {
                        type: 'doughnut',
                        data: {
                            labels: response.carga_operativa.labels,
                            datasets: [{
                                data: response.carga_operativa.data,
                                backgroundColor: [
                                    '#2c3e50', // Slate
                                    '#18bc9c', // Teal
                                    '#3498db', // Blue
                                    '#9b59b6', // Purple
                                    '#e74c3c', // Red
                                    '#f39c12', // Orange
                                    '#1abc9c', // Cyan
                                    '#f1c40f', // Yellow
                                    '#95a5a6'  // Gray
                                ],
                                borderWidth: 0
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
                                        font: { size: 11 }
                                    }
                                }
                            },
                            cutout: '70%'
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
