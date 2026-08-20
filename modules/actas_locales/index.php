<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_actas_locales');
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
    <title>Actas Locales - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                <a href="#actasSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
                    <i class="fa-solid fa-print"></i> <span class="sidebar-text">Expedición de Actas</span>
                </a>
                <ul class="collapse list-unstyled show" id="actasSubmenu">
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
                <a href="#constSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                            <?php echo \Core\Utils::getAvatarHtml(\Core\Auth::getUserName(), 32); ?>
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
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Expedición de Actas Locales</h2>
                    <p class="text-muted small mb-0">Búsqueda, visualización y expedición de copias certificadas locales</p>
                </div>
                <?php if (\Core\Auth::canExportar()): ?>
                <button class="btn btn-success" id="btnExportExcel">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                </button>
                <?php endif; ?>
            </div>

            <!-- Navegación por Pestañas -->
            <ul class="nav nav-pills mb-3" id="actasLocalesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="tab-registros-btn" data-bs-toggle="pill" data-bs-target="#tab-registros" type="button" role="tab">
                        <i class="fa-solid fa-book-open me-1"></i> Consulta de Actas Locales
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold position-relative" id="tab-peticiones-btn" data-bs-toggle="pill" data-bs-target="#tab-peticiones" type="button" role="tab">
                        <i class="fa-solid fa-bolt text-warning me-1"></i> Peticiones de Ventanilla
                        <?php 
                        $pendientesAct = \Core\Services\PeticionRapidaService::getConteoPendientesPorModulo('actas_locales');
                        ?>
                        <span class="badge bg-danger rounded-pill ms-1" id="badgePeticionesAct" style="<?php echo ($pendientesAct > 0) ? '' : 'display:none;'; ?>"><?php echo $pendientesAct; ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="actasLocalesTabContent">
                <!-- Pestaña 1: Consulta de Actas Locales -->
                <div class="tab-pane fade show active" id="tab-registros" role="tabpanel">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold py-3">
                            <i class="fa-solid fa-filter me-1 text-primary"></i> Filtros de Consulta
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="filter_tipo" class="form-label fw-bold small text-muted">Tipo de Acta</label>
                                    <select class="form-select form-select-sm" id="filter_tipo">
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

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
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
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pestaña 2: Peticiones de Ventanilla -->
                <div class="tab-pane fade" id="tab-peticiones" role="tabpanel">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="row align-items-center g-2">
                                <div class="col-auto">
                                    <label for="filter_peticion_estatus" class="col-form-label fw-bold small text-muted">
                                        <i class="fa-solid fa-filter text-primary me-1"></i> Filtrar por Estatus:
                                    </label>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select form-select-sm" id="filter_peticion_estatus">
                                        <option value="">TODOS LOS ESTATUS</option>
                                        <option value="PENDIENTE" selected>PENDIENTES</option>
                                        <option value="EN_PROCESO">EN PROCESO</option>
                                        <option value="ENTREGADO">ENTREGADOS / CONCLUIDOS</option>
                                        <option value="CANCELADO">CANCELADOS</option>
                                    </select>
                                </div>
                                <div class="col-auto ms-auto">
                                    <button class="btn btn-outline-primary btn-sm" id="btnRecargarPeticiones">
                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Actualizar Bandeja
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="peticionesActasTable" class="table table-striped dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Folio Ventanilla</th>
                                            <th>Solicitante</th>
                                            <th>CURP / Contacto</th>
                                            <th>Trámite Solicitado</th>
                                            <th>Detalle / Referencia</th>
                                            <th>Estatus</th>
                                            <th>Fecha Ingreso</th>
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

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="../../assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        const csrfToken = '<?php echo \Core\Auth::generateCSRF(); ?>';

        // 1. Inicializar DataTable de Actas Locales
        const table = $('#actasTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "data.php",
                "data": function(d) {
                    d.tipo_acta = $('#filter_tipo').val();
                }
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
            window.exportToExcelAsync('export_excel.php', {
                search: searchValue,
                tipo_acta: tipoActa,
                csrf_token: csrfToken
            }, 'Exportando Actas Locales');
        });

        // 2. Tabla de Peticiones de Ventanilla para Actas Locales
        const peticionesTable = $('#peticionesActasTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "../peticion_rapida/modulo_peticiones_data.php?modulo=actas_locales",
                "data": function(d) {
                    d.estatus = $('#filter_peticion_estatus').val();
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
                        return `<span class="fw-bold">${data}</span>`;
                    }
                },
                { 
                    "data": "solicitante_curp",
                    "render": function(data, type, row) {
                        let curp = data ? `<span class="badge bg-secondary font-monospace">${data}</span>` : '';
                        let tel = row.solicitante_telefono ? `<div class="small text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i>${row.solicitante_telefono}</div>` : '';
                        return curp + tel;
                    }
                },
                { 
                    "data": "tipo_peticion",
                    "render": function(data) {
                        return `<span class="badge bg-light text-dark border" style="font-size: 0.75rem;">${data === 'COPIA_FIEL' ? 'COPIA FIEL DEL LIBRO' : (data === 'COPIAS_CERTIFICADAS' ? 'COPIAS CERTIFICADAS' : data)}</span>`;
                    }
                },
                { 
                    "data": "detalle",
                    "render": function(data) {
                        return `<span class="text-truncate d-inline-block" style="max-width: 200px;" title="${data}">${data}</span>`;
                    }
                },
                {
                    "data": "estatus",
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
                    "render": function(data, type, row) {
                        let html = `<div class="btn-group btn-group-sm" role="group">`;
                        if (row.estatus === 'PENDIENTE' || row.estatus === 'EN_PROCESO') {
                            html += `
                                <button class="btn btn-warning text-dark btn-buscar-acta" 
                                    data-nombre="${row.solicitante_nombre.replace(/"/g, '&quot;')}" 
                                    data-id="${row.id}" 
                                    data-folio="${row.folio}" 
                                    title="Buscar y Expedir Acta Local">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar Acta
                                </button>
                                <button class="btn btn-outline-success btn-entregar-peticion" data-id="${row.id}" title="Marcar como Entregada">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            `;
                        }
                        html += `
                            <button class="btn btn-outline-primary btn-ticket-peticion" data-id="${row.id}" title="Imprimir Ticket">
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>`;
                        return html;
                    }
                }
            ],
            "order": [[0, "desc"]]
        });

        $('#filter_peticion_estatus, #btnRecargarPeticiones').on('change click', function() {
            peticionesTable.draw();
        });

        // Buscar Acta desde Petición: Cambia a pestaña 1 y filtra por nombre
        $('#peticionesActasTable').on('click', '.btn-buscar-acta', function() {
            const nombre = $(this).data('nombre');
            $('#tab-registros-btn').tab('show');
            table.search(nombre).draw();
            window.showToast('info', 'Búsqueda activada', `Buscando actas para: ${nombre}`);
        });

        // Marcar entregada
        $('#peticionesActasTable').on('click', '.btn-entregar-peticion', function() {
            const id = $(this).data('id');
            $.ajax({
                url: '../peticion_rapida/estado.php',
                type: 'POST',
                data: { id: id, estatus: 'ENTREGADO', csrf_token: csrfToken },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        window.showToast('success', '¡Listo!', response.message);
                        peticionesTable.ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                    }
                }
            });
        });

        // Imprimir Ticket
        $('#peticionesActasTable').on('click', '.btn-ticket-peticion', function() {
            window.open('../peticion_rapida/ticket.php?id=' + $(this).data('id'), '_blank');
        });

        // Detalle de Acta
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

        // Ajustar columnas de DataTables al cambiar pestañas
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function() {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
