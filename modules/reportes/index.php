<?php
// modules/reportes/index.php
require_once '../../core/Auth.php';
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
    <title>Reportes Cruzados - ERP DRC</title>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Generador de Reportes Cruzados</h2>
                    <p class="text-muted small mb-0">Consultas analÃ­ticas consolidadas de todos los actos registrales</p>
                </div>
                <?php if (\Core\Auth::canExportar()): ?>
                <button class="btn btn-excel" id="btnExportGeneralExcel">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Stackable Filters Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-bold">
                    <i class="fa-solid fa-filter text-primary me-2"></i> Filtros de BÃºsqueda Apilables
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="filter_fecha_inicio" class="form-label fw-bold">Fecha Inicio</label>
                            <input type="date" class="form-control" id="filter_fecha_inicio">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_fecha_fin" class="form-label fw-bold">Fecha Fin</label>
                            <input type="date" class="form-control" id="filter_fecha_fin">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_modulo" class="form-label fw-bold">MÃ³dulo</label>
                            <select class="form-select" id="filter_modulo">
                                <option value="">TODOS LOS MÃ“DULOS</option>
                                <option value="nacimientos">Nacimientos</option>
                                <option value="defunciones">Defunciones</option>
                                <option value="inexistencias">Inexistencias</option>
                                <option value="foraneas">ForÃ¡neas</option>
                                <option value="peticiones">Peticiones / Tickets</option>
                                <option value="matrimonios">Matrimonios</option>
                                <option value="divorcios">Divorcios</option>
                                <option value="reconocimientos">Reconocimientos</option>
                                <option value="inscripciones">Inscripciones</option>
                                <option value="tramites_curp">TrÃ¡mites CURP</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter_estatus" class="form-label fw-bold">Estatus</label>
                            <select class="form-select" id="filter_estatus">
                                <option value="">TODOS</option>
                                <option value="PENDIENTE">PENDIENTE / ABIERTA</option>
                                <option value="FINALIZADO">FINALIZADO / VALIDADA / PROCESADO</option>
                                <option value="CANCELADO">CANCELADO / RECHAZADA</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter_operador" class="form-label fw-bold">Operador / Capturista</label>
                            <select class="form-select" id="filter_operador">
                                <option value="">TODOS</option>
                                <?php
                                require_once '../../core/Database.php';
                                try {
                                    $pdoUsers = \Core\Database::getReadConnection();
                                    $stmtUsers = $pdoUsers->query("SELECT id, nombre FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC");
                                    while ($userRow = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $userRow['id'] . '">' . htmlspecialchars($userRow['nombre']) . '</option>';
                                    }
                                } catch (Exception $e) {}
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Dynamic badges wrapper -->
                    <div class="d-flex align-items-center flex-wrap pt-3 border-top" id="activeFiltersContainer" style="display: none !important;">
                        <span class="text-muted me-3 fw-bold" style="font-size: 0.85rem;">Filtros Activos:</span>
                        <div class="d-flex flex-wrap gap-2" id="activeFiltersList"></div>
                        <button class="btn btn-link btn-sm text-danger ms-auto p-0 fw-bold text-decoration-none" id="btnClearAllFilters">
                            <i class="fa-solid fa-trash-can me-1"></i> Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report List Card -->
            <div class="card">
                <div class="card-body">
                    <table id="reportesTable" class="table table-striped dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>MÃ³dulo</th>
                                <th>ID / Folio / Acta</th>
                                <th>Referencia / Ciudadano</th>
                                <th>Fecha Registro</th>
                                <th>Operador / Capturista</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
        // Cargar Notificaciones

        // DataTables setup
        const table = $('#reportesTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "data.php",
                "data": function(d) {
                    d.fecha_inicio = $('#filter_fecha_inicio').val();
                    d.fecha_fin = $('#filter_fecha_fin').val();
                    d.modulo = $('#filter_modulo').val();
                    d.estatus = $('#filter_estatus').val();
                    d.operador_id = $('#filter_operador').val();
                }
            },
            
            "columns": [
                { "data": "modulo" },
                { "data": "folio" },
                { "data": "referencia" },
                { "data": "fecha" },
                { "data": "operador" },
                { 
                    "data": "estatus",
                    "render": function(data) {
                        let badgeClass = 'bg-secondary';
                        if (['PENDIENTE', 'ABIERTA'].includes(data)) badgeClass = 'bg-warning text-dark';
                        if (['FINALIZADO', 'VALIDADA', 'PROCESADO'].includes(data)) badgeClass = 'bg-success';
                        if (['CANCELADO', 'RECHAZADA'].includes(data)) badgeClass = 'bg-danger';
                        return `<span class="badge ${badgeClass}">${data}</span>`;
                    }
                }
            ],
            "order": [[3, "desc"]]
        });

        // Update active filters visual badges
        function updateFilterBadges() {
            const badges = [];
            
            const fechaInicio = $('#filter_fecha_inicio').val();
            if (fechaInicio) {
                badges.push({ id: 'fecha_inicio', label: `Inicio: ${fechaInicio}` });
            }
            
            const fechaFin = $('#filter_fecha_fin').val();
            if (fechaFin) {
                badges.push({ id: 'fecha_fin', label: `Fin: ${fechaFin}` });
            }
            
            const moduloVal = $('#filter_modulo').val();
            if (moduloVal) {
                const moduloText = $('#filter_modulo option:selected').text();
                badges.push({ id: 'modulo', label: `MÃ³dulo: ${moduloText}` });
            }
            
            const estatusVal = $('#filter_estatus').val();
            if (estatusVal) {
                const estatusText = $('#filter_estatus option:selected').text();
                badges.push({ id: 'estatus', label: `Estatus: ${estatusText}` });
            }
            
            const operadorVal = $('#filter_operador').val();
            if (operadorVal) {
                const operadorText = $('#filter_operador option:selected').text();
                badges.push({ id: 'operador', label: `Operador: ${operadorText}` });
            }

            const $container = $('#activeFiltersContainer');
            const $list = $('#activeFiltersList');
            $list.empty();
            
            if (badges.length > 0) {
                $container.attr('style', 'display: flex !important;');
                badges.forEach(badge => {
                    const badgeHtml = `
                        <span class="badge bg-primary text-white py-2 px-3 d-flex align-items-center" style="font-size: 0.85rem; border-radius: 20px;">
                            ${badge.label}
                            <i class="fa-solid fa-xmark ms-2 remove-filter" data-target="${badge.id}" style="cursor: pointer;"></i>
                        </span>
                    `;
                    $list.append(badgeHtml);
                });
            } else {
                $container.attr('style', 'display: none !important;');
            }
        }

        // Draw and update on inputs changes
        $('#filter_fecha_inicio, #filter_fecha_fin, #filter_modulo, #filter_estatus, #filter_operador').on('change input', function() {
            table.draw();
            updateFilterBadges();
        });

        // Remove a single filter badge
        $(document).on('click', '.remove-filter', function() {
            const target = $(this).data('target');
            if (target === 'fecha_inicio') $('#filter_fecha_inicio').val('');
            if (target === 'fecha_fin') $('#filter_fecha_fin').val('');
            if (target === 'modulo') $('#filter_modulo').val('');
            if (target === 'estatus') $('#filter_estatus').val('');
            if (target === 'operador') $('#filter_operador').val('');
            
            table.draw();
            updateFilterBadges();
        });

        // Clear all active filters
        $('#btnClearAllFilters').on('click', function(e) {
            e.preventDefault();
            $('#filter_fecha_inicio').val('');
            $('#filter_fecha_fin').val('');
            $('#filter_modulo').val('');
            $('#filter_estatus').val('');
            $('#filter_operador').val('');
            
            table.draw();
            updateFilterBadges();
        });

        // AJAX background Excel exporter
        $('#btnExportGeneralExcel').on('click', function() {
            window.exportToExcelAsync('export_excel.php', {
                fecha_inicio: $('#filter_fecha_inicio').val(),
                fecha_fin: $('#filter_fecha_fin').val(),
                modulo: $('#filter_modulo').val(),
                estatus: $('#filter_estatus').val(),
                operador_id: $('#filter_operador').val(),
                csrf_token: '<?php echo \Core\Auth::generateCSRF(); ?>'
            }, 'Exportando Reporte General Cruzado');
        });
    });
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
