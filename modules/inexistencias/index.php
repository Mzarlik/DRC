<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_constancias');
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
    <title>Constancias e Inexistencias - ERP DRC</title>
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
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" aria-label="Toggle Sidebar">
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
                    <h2 class="fw-bold mb-0"><i class="fa-solid fa-file-signature text-primary me-2"></i> Módulo de Constancias</h2>
                    <p class="text-muted small mb-0">Gestión de inexistencias de matrimonio, nacimiento, no deudor y descendencia</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (\Core\Auth::canExportar()): ?>
                    <button class="btn btn-excel" id="btnExportExcel">
                        <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createInexistenciaModal">
                        <i class="fa-solid fa-plus me-1"></i> Nueva Constancia
                    </button>
                </div>
            </div>

            <!-- Navegación por Pestañas -->
            <ul class="nav nav-pills mb-3" id="constanciasTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="tab-registros-btn" data-bs-toggle="pill" data-bs-target="#tab-registros" type="button" role="tab">
                        <i class="fa-solid fa-file-circle-check me-1"></i> Constancias Emitidas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold position-relative" id="tab-peticiones-btn" data-bs-toggle="pill" data-bs-target="#tab-peticiones" type="button" role="tab">
                        <i class="fa-solid fa-bolt text-warning me-1"></i> Peticiones de Ventanilla
                        <?php 
                        $pendientesConst = \Core\Services\PeticionRapidaService::getConteoPendientesPorModulo('inexistencias');
                        ?>
                        <span class="badge bg-danger rounded-pill ms-1" id="badgePeticionesConst" style="<?php echo ($pendientesConst > 0) ? '' : 'display:none;'; ?>"><?php echo $pendientesConst; ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="constanciasTabContent">
                <!-- Pestaña 1: Registros Emitidos -->
                <div class="tab-pane fade show active" id="tab-registros" role="tabpanel">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold py-3">
                            <i class="fa-solid fa-filter me-1 text-primary"></i> Filtros de Búsqueda
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <label for="filter_tipo" class="form-label fw-bold small text-muted">Tipo de Constancia</label>
                                     <select class="form-select form-select-sm" id="filter_tipo">
                                         <option value="">TODAS LAS CONSTANCIAS</option>
                                         <?php
                                         $opciones = \Core\Catalogo::getOpciones('tipo_constancia');
                                         if (empty($opciones)) {
                                             $opciones = [
                                                 ['clave' => 'INEXISTENCIA_DESCENDENCIA', 'valor' => 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA'],
                                                 ['clave' => 'NO_DEUDOR', 'valor' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO'],
                                                 ['clave' => 'INEXISTENCIA_MATRIMONIO', 'valor' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO'],
                                                 ['clave' => 'INEXISTENCIA_NACIMIENTO', 'valor' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO']
                                             ];
                                         }
                                         foreach ($opciones as $opc) {
                                             echo '<option value="' . htmlspecialchars($opc['clave'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($opc['valor'], ENT_QUOTES, 'UTF-8') . '</option>';
                                         }
                                         ?>
                                     </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <table id="inexistenciasTable" class="table table-striped dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Línea de Pago</th>
                                        <th>Nombre Completo</th>
                                        <th>Fecha Trámite</th>
                                        <th>Fecha Llegada</th>
                                        <th>Estatus</th>
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
                                <table id="peticionesVentanillaTable" class="table table-striped dt-responsive nowrap w-100">
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

<!-- Modal de Registro de Inexistencia -->
<div class="modal fade" id="createInexistenciaModal" tabindex="-1" aria-labelledby="createInexistenciaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white" style="background: var(--primary-color) !important;">
                <h5 class="modal-title fw-bold" id="createInexistenciaModalLabel"><i class="fa-solid fa-plus me-2"></i> Registrar Nueva Constancia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formInexistenciaModal">
                <input type="hidden" name="csrf_token" value="<?php echo \Core\Auth::generateCSRF(); ?>">
                <input type="hidden" name="peticion_folio_origen" id="modal_peticion_folio_origen" value="">
                
                <div class="modal-body">
                    <!-- Banner de Precarga de Ventanilla -->
                    <div class="alert alert-info py-2 px-3 mb-3 d-none align-items-center" id="alertPeticionOrigen">
                        <i class="fa-solid fa-bolt text-warning me-2 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Trámite canalizado desde Ventanilla de Atención</small>
                            <span class="fw-bold" id="lblPeticionOrigenInfo">Folio: VP-2026-00001</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="modal_tipo_constancia" class="form-label fw-bold">Tipo de Constancia</label>
                            <select class="form-select" id="modal_tipo_constancia" name="tipo_constancia" required>
                                <option value="">Seleccione tipo de constancia oficial...</option>
                                <?php
                                foreach ($opciones as $opc) {
                                    echo '<option value="' . htmlspecialchars($opc['clave'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($opc['valor'], ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="modal_linea_pago" class="form-label fw-bold">Línea de Pago (17-20 dígitos)</label>
                            <input type="number" inputmode="numeric" class="form-control" id="modal_linea_pago" name="linea_pago" required>
                            <div class="form-text">Tratado como cadena para evitar pérdida de precisión.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_nombre_completo" class="form-label fw-bold">Nombre Completo del Ciudadano</label>
                            <input type="text" class="form-control text-uppercase-input" id="modal_nombre_completo" name="nombre_completo" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="modal_fecha_tramite" class="form-label fw-bold">Fecha de Trámite</label>
                            <input type="date" class="form-control" id="modal_fecha_tramite" name="fecha_tramite" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_fecha_llegada" class="form-label fw-bold">Fecha de Llegada (Cálculo Automático +15 días)</label>
                            <input type="date" class="form-control bg-light" id="modal_fecha_llegada" name="fecha_llegada" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_observaciones" class="form-label fw-bold">Observaciones</label>
                        <textarea class="form-control text-uppercase-input" id="modal_observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" style="background: var(--secondary-color); border: none;">
                        <i class="fa-solid fa-save"></i> Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="../../assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        const csrfToken = '<?php echo \Core\Auth::generateCSRF(); ?>';

        // 1. Tabla Principal de Constancias Emitidas
        const table = $('#inexistenciasTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "data.php",
                "data": function(d) {
                    d.tipo_constancia = $('#filter_tipo').val();
                }
            },
            "columns": [
                { "data": "id" },
                { 
                    "data": "tipo_constancia",
                    "render": function(data) {
                        const types = {
                            'INEXISTENCIA_DESCENDENCIA': 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA',
                            'CONSTANCIA_DESCENDENCIA': 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA',
                            'NO_DEUDOR': 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO',
                            'INEXISTENCIA_DEUDOR': 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO',
                            'INEXISTENCIA_MATRIMONIO': 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO',
                            'INEXISTENCIA_NACIMIENTO': 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO'
                        };
                        return types[data] || data;
                    }
                },
                { "data": "linea_pago" },
                { "data": "nombre_completo" },
                { "data": "fecha_tramite" },
                { "data": "fecha_llegada" },
                { 
                    "data": "estatus",
                    "render": function ( data, type, row ) {
                        let badgeClass = 'bg-secondary';
                        if(data === 'PENDIENTE') badgeClass = 'bg-warning text-dark';
                        if(data === 'FINALIZADO') badgeClass = 'bg-success';
                        if(data === 'CANCELADO') badgeClass = 'bg-danger';
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                }
            ],
            "order": [[0, "desc"]]
        });

        $('#filter_tipo').on('change', function() {
            table.draw();
        });

        $('#btnExportExcel').on('click', function() {
            const tipo = $('#filter_tipo').val();
            window.exportToExcelAsync('export_excel.php', {
                tipo: tipo,
                csrf_token: csrfToken
            }, 'Exportando Inexistencias');
        });

        // 2. Tabla de Peticiones de Ventanilla para Constancias
        const tramitesMap = {
            'CONSTANCIA_DESCENDENCIA': 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA',
            'CONSTANCIA_DEUDOR_MOROSO': 'CONSTANCIA DE NO DEUDOR ALIMENTARIO MOROSO',
            'CONSTANCIA_INEXISTENCIA_MATRIMONIO': 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO',
            'CONSTANCIA_INEXISTENCIA_NACIMIENTO': 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO',
            'PASES_CAJA_CONSTANCIAS': 'PASES DE CAJA PARA CONSTANCIAS'
        };

        const peticionesTable = $('#peticionesVentanillaTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "../peticion_rapida/modulo_peticiones_data.php?modulo=inexistencias",
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
                        let label = tramitesMap[data] || data;
                        return `<span class="badge bg-light text-dark border" style="font-size: 0.75rem; white-space: normal;">${label}</span>`;
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
                                <button class="btn btn-warning text-dark btn-atender-peticion" 
                                    data-id="${row.id}" 
                                    data-folio="${row.folio}" 
                                    data-solicitante="${row.solicitante_nombre.replace(/"/g, '&quot;')}"
                                    data-curp="${row.solicitante_curp || ''}"
                                    data-tipo="${row.tipo_peticion}"
                                    data-detalle="${(row.detalle || '').replace(/"/g, '&quot;')}"
                                    title="Atender Petición (Generar Constancia)">
                                    <i class="fa-solid fa-bolt me-1"></i> Atender
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

        // 3. Atender Petición: Precarga y abre el modal de constancias
        $('#peticionesVentanillaTable').on('click', '.btn-atender-peticion', function() {
            const btn = $(this);
            const folio = btn.data('folio');
            const solicitante = btn.data('solicitante');
            const tipo = btn.data('tipo');
            const detalle = btn.data('detalle');

            // Mapeo tipo peticion ventanilla -> tipo constancia
            let tipoConstancia = 'INEXISTENCIA_NACIMIENTO';
            if (tipo === 'CONSTANCIA_DESCENDENCIA') tipoConstancia = 'INEXISTENCIA_DESCENDENCIA';
            else if (tipo === 'CONSTANCIA_DEUDOR_MOROSO') tipoConstancia = 'NO_DEUDOR';
            else if (tipo === 'CONSTANCIA_INEXISTENCIA_MATRIMONIO') tipoConstancia = 'INEXISTENCIA_MATRIMONIO';
            else if (tipo === 'CONSTANCIA_INEXISTENCIA_NACIMIENTO') tipoConstancia = 'INEXISTENCIA_NACIMIENTO';

            // Precargar campos del modal
            $('#modal_peticion_folio_origen').val(folio);
            $('#modal_tipo_constancia').val(tipoConstancia);
            $('#modal_nombre_completo').val(solicitante);
            $('#modal_observaciones').val(`Atención a Petición de Ventanilla ${folio}. ${detalle}`.trim());

            $('#lblPeticionOrigenInfo').text(`Folio: ${folio} — Solicitante: ${solicitante}`);
            $('#alertPeticionOrigen').removeClass('d-none').addClass('d-flex');

            const modal = new bootstrap.Modal(document.getElementById('createInexistenciaModal'));
            modal.show();
        });

        // Reset del modal al cerrarse
        $('#createInexistenciaModal').on('hidden.bs.modal', function () {
            $('#formInexistenciaModal')[0].reset();
            $('#modal_peticion_folio_origen').val('');
            $('#alertPeticionOrigen').addClass('d-none').removeClass('d-flex');
        });

        // 4. Marcar entregada desde la tabla de peticiones
        $('#peticionesVentanillaTable').on('click', '.btn-entregar-peticion', function() {
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

        // 5. Imprimir Ticket
        $('#peticionesVentanillaTable').on('click', '.btn-ticket-peticion', function() {
            window.open('../peticion_rapida/ticket.php?id=' + $(this).data('id'), '_blank');
        });

        // 6. Calcular fecha de llegada automáticamente en el modal (Trámite + 15 días)
        function calcularFechaLlegadaModal() {
            let fechaTramite = $('#modal_fecha_tramite').val();
            if(fechaTramite) {
                let date2 = new Date(fechaTramite + 'T00:00:00');
                date2.setDate(date2.getDate() + 15);
                let yyyy = date2.getFullYear();
                let mm = String(date2.getMonth() + 1).padStart(2, '0');
                let dd = String(date2.getDate()).padStart(2, '0');
                
                $('#modal_fecha_llegada').val(`${yyyy}-${mm}-${dd}`);
            }
        }
        $(document).on('change', '#modal_fecha_tramite', calcularFechaLlegadaModal);
        $(document).on('show.bs.modal', '#createInexistenciaModal', function() {
            if (!$('#modal_fecha_tramite').val()) {
                const today = new Date().toISOString().split('T')[0];
                $('#modal_fecha_tramite').val(today);
            }
            calcularFechaLlegadaModal();
        });

        // 7. Envío AJAX del formulario del Modal
        $('#formInexistenciaModal').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: 'save.php',
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if(response.status === 'success') {
                        const modalEl = document.getElementById('createInexistenciaModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        $form[0].reset();
                        table.ajax.reload(null, false);
                        peticionesTable.ajax.reload(null, false);
                        window.showToast('success', '¡Guardado!', 'El registro y la petición se han procesado exitosamente.');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Ocurrió un error al procesar la solicitud.',
                            confirmButtonColor: 'var(--primary-color)'
                        });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Crítico',
                        text: 'No se pudo conectar con el servidor.',
                        confirmButtonColor: 'var(--primary-color)'
                    });
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
