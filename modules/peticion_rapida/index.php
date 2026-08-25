<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
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
    <title>Petición Rápida (Ventanilla) - ERP DRC</title>
    <link href="../../assets/css/fonts.css" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/vendor/fontawesome/css/all.min.css">
    <link href="../../assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../../assets/vendor/datatables/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="../../assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
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
                        <a class="nav-link dropdown-toggle text-dark position-relative no-caret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bell fa-lg"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="font-size: 0.65rem; display: none;">0</span>
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

                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo \Core\Utils::getAvatarHtml(\Core\Auth::getUserName(), 32); ?>
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
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-table-list text-primary me-2"></i> Control de Peticiones de Ventanilla</h2>
                    <p class="text-muted small mb-0">Historial, búsqueda y seguimiento de trámites en mostrador</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-excel" id="btnExportExcel">
                        <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                    </button>
                    <a href="reporte_diario.php" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-invoice me-1"></i> Reporte Diario Oficial
                    </a>
                    <a href="create.php" class="btn btn-warning text-dark fw-bold">
                        <i class="fa-solid fa-bolt me-1"></i> + Nueva Petición Rápida
                    </a>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="row align-items-center g-2">
                        <div class="col-auto">
                            <label for="filter_estatus_pv" class="col-form-label fw-bold small text-muted">
                                <i class="fa-solid fa-filter text-primary me-1"></i> Filtrar por Estatus:
                            </label>
                        </div>
                        <div class="col-auto">
                            <select class="form-select form-select-sm" id="filter_estatus_pv">
                                <option value="">TODOS LOS ESTATUS</option>
                                <option value="PENDIENTE">PENDIENTES</option>
                                <option value="EN_PROCESO">EN PROCESO</option>
                                <option value="ENTREGADO">ENTREGADOS</option>
                                <option value="CANCELADO">CANCELADOS</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="pvTable" class="table table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Solicitante</th>
                                    <th>CURP / Contacto</th>
                                    <th>Tipo de Petición</th>
                                    <th>Detalle / Referencia</th>
                                    <th>Estatus</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="../../assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="../../assets/js/seguimiento.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = '<?php echo \Core\Auth::generateCSRF(); ?>';

    const tramitesMap = <?php echo json_encode(array_map(fn($t) => "[{$t['codigo']}] {$t['nombre']}", \Core\Services\PeticionRapidaService::TRAMITES)); ?>;

    const pvTable = $('#pvTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "data.php",
            "data": function(d) {
                d.estatus = $('#filter_estatus_pv').val();
            }
        },
        "columns": [
            { 
                "data": "folio",
                "render": function(data) {
                    return `<strong class="text-primary font-monospace">${data}</strong>`;
                }
            },
            { 
                "data": "solicitante_nombre",
                "render": function(data) {
                    return `<span class="fw-semibold">${data}</span>`;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    let curp = row.solicitante_curp ? `<small class="d-block text-muted font-monospace">${row.solicitante_curp}</small>` : '';
                    let tel = row.solicitante_telefono ? `<small class="d-block text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i>${row.solicitante_telefono}</small>` : '';
                    return curp || tel ? `${curp}${tel}` : '<span class="text-muted small">Sin datos</span>';
                }
            },
            { 
                "data": "tipo_peticion",
                "render": function(data) {
                    let label = tramitesMap[data] || data;
                    return `<span class="badge bg-light text-dark border" style="font-size: 0.75rem; white-space: normal; text-align: left;">${label}</span>`;
                }
            },
            { 
                "data": "detalle",
                "render": function(data) {
                    return `<span class="text-truncate d-inline-block" style="max-width: 230px;" title="${data}">${data}</span>`;
                }
            },
            {
                "data": "estatus",
                "responsivePriority": 2,
                "render": function(data) {
                    let badgeClass = 'badge-pendiente';
                    let label = data;
                    if (data === 'ENTREGADO') { badgeClass = 'badge-vivo'; label = 'ENTREGADO'; }
                    else if (data === 'EN_PROCESO') { badgeClass = 'badge-finalizado'; label = 'EN PROCESO'; }
                    else if (data === 'CANCELADO') { badgeClass = 'badge-finado'; label = 'CANCELADO'; }
                    return `<span class="badge-status ${badgeClass}">${label}</span>`;
                }
            },
            { 
                "data": "creado_en",
                "render": function(data) {
                    return `<small class="text-muted">${data ? data.substring(0, 16) : ''}</small>`;
                }
            },
            {
                "data": null,
                "orderable": false,
                "responsivePriority": 1,
                "render": function(data, type, row) {
                    let html = `
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary btn-seguimiento" data-id="${row.id}" title="Abrir Seguimiento">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-ticket" data-id="${row.id}" title="Imprimir Ticket">
                                <i class="fa-solid fa-print"></i>
                            </button>
                            <a href="edit.php?id=${row.id}" class="btn btn-outline-secondary" title="Editar Petición">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="../peticiones/create.php?solicitante=${encodeURIComponent(row.solicitante_nombre)}&curp=${encodeURIComponent(row.solicitante_curp || '')}&tipo=${encodeURIComponent(row.tipo_peticion)}&detalle=${encodeURIComponent(row.detalle)}&folio_origen=${encodeURIComponent(row.folio)}" class="btn btn-outline-info" title="Escalar a Ventanilla de Seguimiento (Expediente / Dictamen)">
                                <i class="fa-solid fa-folder-tree"></i>
                            </a>
                    `;
                    if (row.estatus === 'PENDIENTE' || row.estatus === 'EN_PROCESO') {
                        html += `
                            <button class="btn btn-outline-success btn-entregar" data-id="${row.id}" title="Marcar como Entregado">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        `;
                    }
                    html += `
                            <button class="btn btn-outline-danger btn-eliminar" data-id="${row.id}" data-folio="${row.folio}" title="Eliminar Petición">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    return html;
                }
            }
        ],
        "order": [[0, "desc"]]
    });

    // Imprimir Ticket
    $('#pvTable').on('click', '.btn-ticket', function() {
        window.open('ticket.php?id=' + $(this).data('id'), '_blank');
    });

    // Seguimiento de Peticiones (modal con detalle y reactivación)
    $('#pvTable').on('click', '.btn-seguimiento', function() {
        const $tr = $(this).closest('tr');
        let row = pvTable.row($tr).data();
        if (!row) row = pvTable.row($tr.prevAll('tr.parent').first()).data();
        if (!row) {
            const id = String($(this).data('id'));
            row = pvTable.rows().data().toArray().find(function(r) { return String(r.id) === id; });
        }
        if (!row) return;

        const acciones = {};
        if (row.estatus !== 'PENDIENTE') {
            acciones[row.estatus] = [
                { key: 'REACTIVAR', label: 'Corregir: Reactivar a PENDIENTE', icono: 'fa-rotate-left', clase: 'btn-warning text-dark', requiereMotivo: true,
                  titulo: '¿Reactivar la petición?', texto: 'La petición volverá a PENDIENTE para corregir un error de operación. Se requiere el motivo.' }
            ];
        }

        DrcSeguimiento.open({
            titulo: 'Seguimiento de Petición de Ventanilla',
            endpoint: 'estado.php',
            id: row.id,
            csrf: csrfToken,
            campos: [
                ['FOLIO', `<strong class="text-primary font-monospace">${row.folio}</strong>`],
                ['SOLICITANTE', `<strong>${row.solicitante_nombre}</strong>`],
                ['CURP', row.solicitante_curp ? `<span class="font-monospace">${row.solicitante_curp}</span>` : 'SIN CURP'],
                ['TRÁMITE', tramitesMap[row.tipo_peticion] || row.tipo_peticion],
                ['DETALLE / REFERENCIA', `<span style="white-space: pre-wrap;">${row.detalle || '—'}</span>`],
                ['FECHA DE INGRESO', row.creado_en ? row.creado_en.substring(0, 16) : '—']
            ],
            estatus: row.estatus,
            banner: {
                'PENDIENTE':  { clase: 'alert-warning', icono: 'fa-hourglass-half', texto: 'Petición en espera de atención en ventanilla.' },
                'EN_PROCESO': { clase: 'alert-info',    icono: 'fa-gears',          texto: 'Petición en proceso de atención.' },
                'ENTREGADO':  { clase: 'alert-success', icono: 'fa-circle-check',   texto: 'Petición entregada al solicitante.' },
                'CANCELADO':  { clase: 'alert-danger',  icono: 'fa-ban',            texto: 'Petición cancelada.' }
            },
            acciones: acciones,
            onSuccess: function() {
                pvTable.ajax.reload(null, false);
            }
        });
    });

    // Cambiar Estatus
    function cambiarEstatus(id, estatus) {
        $.ajax({
            url: 'estado.php',
            type: 'POST',
            data: { id: id, estatus: estatus, csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Listo', text: response.message, confirmButtonColor: 'var(--secondary-color)' });
                    pvTable.ajax.reload(null, false);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    }

    $('#btnExportExcel').on('click', function() {
        const search = pvTable.search();
        window.exportToExcelAsync('export_excel.php', {
            search: search,
            csrf_token: csrfToken
        }, 'Exportando Peticiones de Ventanilla');
    });

    $('#pvTable').on('click', '.btn-entregar', function() {
        cambiarEstatus($(this).data('id'), 'ENTREGADO');
    });

    // Soft Delete
    $('#pvTable').on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        const folio = $(this).data('folio');
        Swal.fire({
            title: '¿Eliminar petición?',
            html: `¿Está seguro de eliminar la petición <strong class="text-danger">${folio}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--color-danger)',
            cancelButtonColor: '#64748B'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete.php',
                    type: 'POST',
                    data: { id: id, csrf_token: csrfToken },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Eliminado', text: response.message, confirmButtonColor: 'var(--secondary-color)' });
                            pvTable.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo procesar la solicitud.', confirmButtonColor: 'var(--primary-color)' });
                    }
                });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
