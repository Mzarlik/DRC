<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_registro_defunciones');
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
    <title>Actas de DefunciÃ³n - ERP DRC</title>
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
                    <i class="fa-solid fa-address-book"></i> <span class="sidebar-text">CatÃ¡logos</span>
                </a>
                <ul class="collapse list-unstyled <?php echo ($current_module == 'ciudadanos') ? 'show' : ''; ?>" id="catSubmenu">
                    <li class="<?php echo ($current_module == 'ciudadanos') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'ciudadanos') ? 'index.php' : $path_prefix . 'ciudadanos/index.php'; ?>"><i class="fa-solid fa-users"></i> <span class="sidebar-text">Ciudadanos</span></a></li>
                </ul>
            </li>

            <!-- Registros de Actos (OficialÃ­a) -->
            <?php if (\Core\Auth::hasPermission('permiso_registro_nacimientos') || \Core\Auth::hasPermission('permiso_registro_matrimonios') || \Core\Auth::hasPermission('permiso_registro_divorcios') || \Core\Auth::hasPermission('permiso_registro_defunciones') || \Core\Auth::hasPermission('permiso_registro_inscripciones') || \Core\Auth::hasPermission('permiso_registro_reconocimientos')): ?>
            <li class="<?php echo in_array($current_module, ['nacimientos', 'matrimonios', 'divorcios', 'defunciones', 'inscripciones', 'reconocimientos']) ? 'active' : ''; ?>">
                <a href="#vitalesSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
                    <i class="fa-solid fa-heart-pulse"></i> <span class="sidebar-text">Registro de Actos</span>
                </a>
                <ul class="collapse list-unstyled show" id="vitalesSubmenu">
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

            <!-- ExpediciÃ³n de Actas -->
            <?php if (\Core\Auth::hasPermission('permiso_actas_locales') || \Core\Auth::hasPermission('permiso_actas_foraneas')): ?>
            <li class="<?php echo in_array($current_module, ['actas_locales', 'foraneas']) ? 'active' : ''; ?>">
                <a href="#actasSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-print"></i> <span class="sidebar-text">ExpediciÃ³n de Actas</span>
                </a>
                <ul class="collapse list-unstyled <?php echo in_array($current_module, ['actas_locales', 'foraneas']) ? 'show' : ''; ?>" id="actasSubmenu">
                    <?php if (\Core\Auth::hasPermission('permiso_actas_locales')): ?>
                    <li class="<?php echo ($current_module == 'actas_locales') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'actas_locales') ? 'index.php' : $path_prefix . 'actas_locales/index.php'; ?>"><i class="fa-solid fa-file-invoice"></i> <span class="sidebar-text">Actas Locales</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_actas_foraneas')): ?>
                    <li class="<?php echo ($current_module == 'foraneas') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'foraneas') ? 'index.php' : $path_prefix . 'foraneas/index.php'; ?>"><i class="fa-solid fa-plane-arrival"></i> <span class="sidebar-text">Actas ForÃ¡neas</span></a></li>
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
                <a href="#curpSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-id-card"></i> <span class="sidebar-text">Servicios CURP</span>
                </a>
                <ul class="collapse list-unstyled <?php echo ($current_module == 'curp') ? 'show' : ''; ?>" id="curpSubmenu">
                    <li class="<?php echo ($current_module == 'curp') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'curp') ? 'index.php' : $path_prefix . 'curp/index.php'; ?>"><i class="fa-solid fa-fingerprint"></i> <span class="sidebar-text">TrÃ¡mites CURP</span></a></li>
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
                            <i class="fa-solid fa-bolt text-warning"></i> <span class="sidebar-text">PeticiÃ³n RÃ¡pida</span>
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

            <!-- AdministraciÃ³n (Admin / Supervisor) -->
            <?php if (in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'COORDINADOR', 'SUPERVISOR'])): ?>
            <li class="<?php echo ($current_module == 'public' && (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php')) ? 'active' : ''; ?>">
                <a href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-users-gear"></i> <span class="sidebar-text">AdministraciÃ³n</span>
                </a>
                <ul class="collapse list-unstyled <?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'show' : ''; ?>" id="adminSubmenu">
                    <?php if (($_SESSION['user_rol'] ?? '') === 'ADMIN'): ?>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'public') ? 'usuarios.php' : '../../public/usuarios.php'; ?>"><i class="fa-solid fa-user-shield"></i> <span class="sidebar-text">Usuarios y Permisos</span></a></li>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'public') ? 'auditoria.php' : '../../public/auditoria.php'; ?>"><i class="fa-solid fa-clipboard-list"></i> <span class="sidebar-text">AuditorÃ­a y Errores</span></a></li>
                    <?php endif; ?>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'active' : ''; ?>"><a href="<?php echo ($current_module == 'public') ? 'catalogos.php' : '../../public/catalogos.php'; ?>"><i class="fa-solid fa-gears"></i> <span class="sidebar-text">Conceptos y CatÃ¡logos</span></a></li>
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
                            <li><a class="dropdown-item text-danger" href="<?php echo $logout_link; ?>"><i class="fa-solid fa-right-from-bracket fa-sm me-2"></i> Cerrar SesiÃ³n</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-book-skull text-danger me-2"></i> Actas de DefunciÃ³n</h2>
                    <p class="text-muted small mb-0">Libro y registro digital de defunciones oficiales</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (\Core\Auth::canExportar()): ?>
                    <button class="btn btn-success" id="btnExportExcel" style="background: var(--accent-color, #27ae60); border: none;">
                        <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                    </button>
                    <?php endif; ?>
                    <a href="create.php" class="btn btn-danger fw-bold" style="border: none;">
                        <i class="fa-solid fa-plus me-1"></i> Registrar DefunciÃ³n
                    </a>
                </div>
            </div>

            <!-- NavegaciÃ³n por PestaÃ±as -->
            <ul class="nav nav-pills mb-3" id="defuncionesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="tab-registros-btn" data-bs-toggle="pill" data-bs-target="#tab-registros" type="button" role="tab">
                        <i class="fa-solid fa-book-skull me-1"></i> Actas de DefunciÃ³n Registradas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold position-relative" id="tab-peticiones-btn" data-bs-toggle="pill" data-bs-target="#tab-peticiones" type="button" role="tab">
                        <i class="fa-solid fa-bolt text-warning me-1"></i> Peticiones de Ventanilla
                        <?php 
                        $pendientesDef = \Core\Services\PeticionRapidaService::getConteoPendientesPorModulo('defunciones');
                        ?>
                        <span class="badge bg-danger rounded-pill ms-1" id="badgePeticionesDef" style="<?php echo ($pendientesDef > 0) ? '' : 'display:none;'; ?>"><?php echo $pendientesDef; ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="defuncionesTabContent">
                <!-- PestaÃ±a 1: Registros de Defunciones -->
                <div class="tab-pane fade show active" id="tab-registros" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <table id="defuncionesTable" class="table table-striped dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>No. Acta</th>
                                        <th>Ciudadano (Finado)</th>
                                        <th>Fecha DefunciÃ³n</th>
                                        <th>Fecha Registro</th>
                                        <th>Causa</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PestaÃ±a 2: Peticiones de Ventanilla -->
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
                                <table id="peticionesDefTable" class="table table-striped dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Folio Ventanilla</th>
                                            <th>Solicitante</th>
                                            <th>CURP / Contacto</th>
                                            <th>TrÃ¡mite Solicitado</th>
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

        // 1. Tabla de Actas de DefunciÃ³n
        var table = $('#defuncionesTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": "data.php",
            "columns": [
                { "data": "numero_acta" },
                { "data": "nombre_completo" },
                { "data": "fecha_defuncion" },
                { "data": "fecha_registro" },
                { "data": "causa_muerte" }
            ],
            "order": [[3, "desc"]]
        });

        // Exportar a Excel
        $('#btnExportExcel').on('click', function() {
            const searchValue = table.search();
            window.exportToExcelAsync('export_excel.php', {
                search: searchValue,
                csrf_token: csrfToken
            }, 'Exportando Actas de DefunciÃ³n');
        });

        // 2. Tabla de Peticiones de Ventanilla para Defunciones
        const peticionesTable = $('#peticionesDefTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "../peticion_rapida/modulo_peticiones_data.php?modulo=defunciones",
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
                        let label = data;
                        if (data === 'REGISTRO_DEFUNCION') label = 'REGISTRO DE DEFUNCIÃ“N';
                        else if (data === 'ORDEN_INHUMACION_CREMACION') label = 'ORDEN DE INHUMACIÃ“N / CREMACIÃ“N';
                        else if (data === 'PASES_CAJA_DEFUNCION') label = 'PASES DE CAJA PARA DEFUNCIÃ“N';
                        return `<span class="badge bg-light text-dark border" style="font-size: 0.75rem;">${label}</span>`;
                    }
                },
                { 
                    "data": "detalle",
                    "render": function(data) {
                        return `<span class="text-truncate d-inline-block" style="max-width: 220px;" title="${data}">${data}</span>`;
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
                                <a href="create.php?solicitante=${encodeURIComponent(row.solicitante_nombre)}&curp=${encodeURIComponent(row.solicitante_curp || '')}&folio_origen=${encodeURIComponent(row.folio)}" 
                                    class="btn btn-warning text-dark" title="Atender PeticiÃ³n (Registrar DefunciÃ³n)">
                                    <i class="fa-solid fa-bolt me-1"></i> Atender
                                </a>
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

        // Marcar entregada
        $('#peticionesDefTable').on('click', '.btn-entregar-peticion', function() {
            const id = $(this).data('id');
            $.ajax({
                url: '../peticion_rapida/estado.php',
                type: 'POST',
                data: { id: id, estatus: 'ENTREGADO', csrf_token: csrfToken },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        window.showToast('success', 'Â¡Listo!', response.message);
                        peticionesTable.ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                    }
                }
            });
        });

        // Imprimir Ticket
        $('#peticionesDefTable').on('click', '.btn-ticket-peticion', function() {
            window.open('../peticion_rapida/ticket.php?id=' + $(this).data('id'), '_blank');
        });

        // Ajustar columnas de DataTables al cambiar pestaÃ±as
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function() {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
