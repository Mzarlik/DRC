<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_actas_locales');

$current_module = 'actas_locales';
$path_prefix = '../';
$db_link = '../../public/index.php';
$logout_link = '../../public/logout.php';
$profile_link = '../../public/perfil.php';
$notif_api = '../../public/api/notifications.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actas Locales - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                <a href="#actasSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
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

            <!-- Administración (Admin / Supervisor) -->
            <?php if (in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'SUPERVISOR'])): ?>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Consulta de Actas Locales</h2>
                <button class="btn btn-success" id="btnExportExcel" style="background: var(--accent-color, #27ae60); border: none;">
                    <i class="fa-solid fa-file-excel"></i> Exportar consulta a Excel
                </button>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-white fw-bold">
                    Filtros de Consulta
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="filter_tipo" class="form-label fw-bold">Tipo de Acta</label>
                            <select class="form-select" id="filter_tipo">
                                <option value="">TODAS LAS ACTAS</option>
                                <option value="NACIMIENTO">NACIMIENTO</option>
                                <option value="MATRIMONIO">MATRIMONIO</option>
                                <option value="DIVORCIO">DIVORCIO</option>
                                <option value="DEFUNCION">DEFUNCIÓN</option>
                                <option value="RECONOCIMIENTO">RECONOCIMIENTO</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="actasTable" class="table table-striped dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>No. Acta</th>
                                <th>Tipo</th>
                                <th>Primer Involucrado / Ciudadano</th>
                                <th>Segundo Involucrado (Si aplica)</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-skeleton">
                            <tr>
                                <td><span class="skeleton" style="width: 80%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 67%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 70%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 82%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 76%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 75%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 62%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 71%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 76%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 79%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 72%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 62%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 64%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 69%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 89%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 94%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 65%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 85%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 83%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 94%; height: 16px;"></span></td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Offcanvas para Detalles en Móvil -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasDetails" aria-labelledby="offcanvasDetailsLabel" style="height: 70vh; border-top-left-radius: 16px; border-top-right-radius: 16px; background-color: var(--card-bg, #ffffff); color: var(--text-color, #2c3e50);">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasDetailsLabel">Detalle de Acta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" style="filter: var(--close-btn-filter);"></button>
    </div>
    <div class="offcanvas-body" id="offcanvasDetailsBody">
        <!-- Contenido dinámico -->
    </div>
    <div class="offcanvas-footer p-3 border-top d-flex gap-2" style="background-color: var(--navbar-bg, #f8f9fa);">
        <a href="#" id="btnOffcanvasPrint" class="btn btn-success w-100" target="_blank" style="background: var(--accent-color, #27ae60); border: none;"><i class="fa-solid fa-print"></i> Imprimir PDF</a>
        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="offcanvas">Cerrar</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
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

        // Inicializar DataTable
        const table = $('#actasTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "data.php",
                "data": function(d) {
                    d.tipo_acta = $('#filter_tipo').val();
                }
            },
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
            },
            "columns": [
                { "data": "numero_acta" },
                { 
                    "data": "tipo_acta",
                    "render": function(data) {
                        let badgeColor = "bg-secondary";
                        if(data === 'NACIMIENTO') badgeColor = "bg-primary";
                        if(data === 'MATRIMONIO') badgeColor = "bg-success";
                        if(data === 'DIVORCIO') badgeColor = "bg-info text-dark";
                        if(data === 'DEFUNCION') badgeColor = "bg-danger";
                        if(data === 'RECONOCIMIENTO') badgeColor = "bg-warning text-dark";
                        return `<span class="badge ${badgeColor}">${data}</span>`;
                    }
                },
                { "data": "ciudadano_1" },
                { 
                    "data": "ciudadano_2",
                    "render": function(data) {
                        return data ? data : `<span class="text-muted small">N/A</span>`;
                    }
                },
                { "data": "fecha_registro" },
                {
                    "data": null,
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `<button class="btn btn-sm btn-outline-primary btn-details" data-tipo="${row.tipo_acta}" data-id="${row.registro_id}">
                                    <i class="fa-solid fa-eye"></i> Detalle
                                </button>`;
                    }
                }
            ],
            "order": [[4, "desc"]]
        });

        $('#filter_tipo').on('change', function() {
            table.draw();
        });

        // Exportar a Excel
        $('#btnExportExcel').on('click', function() {
            const searchValue = table.search();
            const tipoActa = $('#filter_tipo').val();
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: 'export_excel.php',
                type: 'GET',
                data: { search: searchValue, tipo_acta: tipoActa },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Generando Reporte',
                            text: response.message,
                            confirmButtonColor: 'var(--secondary-color)'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            confirmButtonColor: 'var(--primary-color)'
                        });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Crítico',
                        text: 'No se pudo conectar con el servidor para procesar la exportación.',
                        confirmButtonColor: 'var(--primary-color)'
                    });
                }
            });
        });

        // Detalle de Acta mediante SweetAlert2 o Offcanvas en móvil
        $('#actasTable').on('click', '.btn-details', function() {
            const tipo = $(this).data('tipo');
            const id = $(this).data('id');
            const isMobile = window.innerWidth < 768;
            
            if (!isMobile) {
                Swal.fire({
                    title: 'Cargando Detalles...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }

            $.ajax({
                url: 'get_details.php',
                type: 'GET',
                data: { tipo: tipo, id: id },
                dataType: 'json',
                success: function(response) {
                    if (!isMobile) {
                        Swal.close();
                    }
                    if(response.status === 'success') {
                        let data = response.data;
                        let htmlContent = `<div class="text-start px-3" style="${isMobile ? '' : 'max-height: 400px; overflow-y: auto;'}">`;
                        
                        htmlContent += `<p class="mb-1"><strong>Número de Acta:</strong> ${data.numero_acta}</p>`;
                        htmlContent += `<p class="mb-1"><strong>Fecha de Registro:</strong> ${data.fecha_registro}</p>`;
                        htmlContent += `<hr class="my-2">`;

                        if (tipo === 'NACIMIENTO') {
                            htmlContent += `<h6 class="fw-bold text-primary mb-2">Registrado (Recién Nacido):</h6>`;
                            htmlContent += `<p class="mb-1"><strong>Nombre:</strong> ${data.c_nombre} ${data.c_app} ${data.c_apm}</p>`;
                            htmlContent += `<p class="mb-1"><strong>CURP:</strong> ${data.c_curp || 'SIN CURP'}</p>`;
                            htmlContent += `<p class="mb-1"><strong>Fecha de Nacimiento:</strong> ${data.c_fnac}</p>`;
                            htmlContent += `<p class="mb-2"><strong>Lugar de Nacimiento:</strong> ${data.lugar_nacimiento}</p>`;

                            if(data.p_nombre) {
                                htmlContent += `<h6 class="fw-bold text-secondary mb-1">Padre:</h6>`;
                                htmlContent += `<p class="mb-2">${data.p_nombre} ${data.p_app} ${data.p_apm} (${data.p_curp || 'SIN CURP'})</p>`;
                            }
                            if(data.m_nombre) {
                                htmlContent += `<h6 class="fw-bold text-secondary mb-1">Madre:</h6>`;
                                htmlContent += `<p class="mb-0">${data.m_nombre} ${data.m_app} ${data.m_apm} (${data.m_curp || 'SIN CURP'})</p>`;
                            }
                        } else if (tipo === 'MATRIMONIO') {
                            htmlContent += `<h6 class="fw-bold text-primary mb-1">Contrayente 1:</h6>`;
                            htmlContent += `<p class="mb-2">${data.c1_nombre} ${data.c1_app} ${data.c1_apm} (${data.c1_curp || 'SIN CURP'})</p>`;
                            
                            htmlContent += `<h6 class="fw-bold text-primary mb-1">Contrayente 2:</h6>`;
                            htmlContent += `<p class="mb-2">${data.c2_nombre} ${data.c2_app} ${data.c2_apm} (${data.c2_curp || 'SIN CURP'})</p>`;
                            
                            htmlContent += `<hr class="my-2">`;
                            htmlContent += `<p class="mb-0"><strong>Régimen Patrimonial:</strong> ${data.regimen_patrimonial}</p>`;
                        } else if (tipo === 'DIVORCIO') {
                            htmlContent += `<h6 class="fw-bold text-primary mb-1">Divorciado 1:</h6>`;
                            htmlContent += `<p class="mb-2">${data.c1_nombre} ${data.c1_app} ${data.c1_apm} (${data.c1_curp || 'SIN CURP'})</p>`;
                            
                            htmlContent += `<h6 class="fw-bold text-primary mb-1">Divorciado 2:</h6>`;
                            htmlContent += `<p class="mb-2">${data.c2_nombre} ${data.c2_app} ${data.c2_apm} (${data.c2_curp || 'SIN CURP'})</p>`;
                            
                            htmlContent += `<hr class="my-2">`;
                            htmlContent += `<p class="mb-0"><strong>Tipo de Divorcio:</strong> ${data.tipo_divorcio}</p>`;
                        } else if (tipo === 'DEFUNCION') {
                            htmlContent += `<h6 class="fw-bold text-danger mb-2">Finado:</h6>`;
                            htmlContent += `<p class="mb-1"><strong>Nombre:</strong> ${data.c_nombre} ${data.c_app} ${data.c_apm}</p>`;
                            htmlContent += `<p class="mb-1"><strong>CURP:</strong> ${data.c_curp || 'SIN CURP'}</p>`;
                            htmlContent += `<p class="mb-1"><strong>Fecha de Defunción:</strong> ${data.fecha_defuncion}</p>`;
                            htmlContent += `<p class="mb-0"><strong>Causa de Muerte:</strong> ${data.causa_muerte}</p>`;
                        } else if (tipo === 'RECONOCIMIENTO') {
                            htmlContent += `<h6 class="fw-bold text-primary mb-1">Reconocido:</h6>`;
                            htmlContent += `<p class="mb-2">${data.c1_nombre} ${data.c1_app} ${data.c1_apm} (${data.c1_curp || 'SIN CURP'})</p>`;
                            
                            htmlContent += `<h6 class="fw-bold text-primary mb-1">Reconocedor:</h6>`;
                            htmlContent += `<p class="mb-0">${data.c2_nombre} ${data.c2_app} ${data.c2_apm} (${data.c2_curp || 'SIN CURP'})</p>`;
                        }

                        htmlContent += `</div>`;

                        if (isMobile) {
                            $('#offcanvasDetailsLabel').text(`Acta de ${tipo}`);
                            $('#offcanvasDetailsBody').html(htmlContent);
                            $('#btnOffcanvasPrint').attr('href', `pdf.php?tipo=${tipo}&id=${id}`);
                            const bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasDetails'));
                            bsOffcanvas.show();
                        } else {
                            Swal.fire({
                                title: `Acta de ${tipo}`,
                                html: htmlContent,
                                showCancelButton: true,
                                confirmButtonText: '<i class="fa-solid fa-print"></i> Imprimir / Descargar PDF',
                                cancelButtonText: 'Cerrar',
                                confirmButtonColor: '#18bc9c',
                                cancelButtonColor: '#6c757d'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.open(`pdf.php?tipo=${tipo}&id=${id}`, '_blank');
                                }
                            });
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    if (!isMobile) {
                        Swal.close();
                    }
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                }
            });
        });
    });
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
