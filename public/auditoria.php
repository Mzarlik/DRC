<?php
require_once '../core/Auth.php';
\Core\Auth::check();

if (($_SESSION['user_rol'] ?? '') !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

require_once '../core/Database.php';
use Core\Database;
$pdo = Database::getReadConnection();
$modulosAuditoria = $pdo->query("SELECT DISTINCT modulo FROM auditoria_logs ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
$accionesAuditoria = $pdo->query("SELECT DISTINCT accion FROM auditoria_logs ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);

$current_module = 'public';
$path_prefix = '../modules/';
$db_link = 'index.php';
$logout_link = 'logout.php';
$profile_link = 'perfil.php';
$notif_api = 'api/notifications.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría y Errores - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
    <link href="../assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
    <style>
        .stack-trace {
            max-height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 10px;
            font-size: 0.85em;
            font-family: monospace;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .dark-mode .stack-trace {
            background: #2b3035;
            color: #dee2e6;
        }
    </style>
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
                            <li><a class="dropdown-item text-danger" href="<?php echo $logout_link; ?>"><i class="fa-solid fa-right-from-bracket fa-sm me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <h2 class="mb-4">Auditoría y Registro de Errores</h2>
            
            <ul class="nav nav-tabs mb-4" id="auditoriaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="acciones-tab" data-bs-toggle="tab" data-bs-target="#acciones" type="button" role="tab" aria-controls="acciones" aria-selected="true">
                        <i class="fa-solid fa-shoe-prints me-2"></i> Registro de Acciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-danger" id="errores-tab" data-bs-toggle="tab" data-bs-target="#errores" type="button" role="tab" aria-controls="errores" aria-selected="false">
                        <i class="fa-solid fa-bug me-2"></i> Registro de Errores
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="auditoriaTabsContent">
                <!-- Pestaña Acciones -->
                <div class="tab-pane fade show active" id="acciones" role="tabpanel" aria-labelledby="acciones-tab">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label for="filtroModulo" class="form-label small fw-bold mb-1">Módulo</label>
                                    <select id="filtroModulo" class="form-select form-select-sm">
                                        <option value="">Todos los módulos</option>
                                        <?php foreach ($modulosAuditoria as $m): ?>
                                        <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroAccion" class="form-label small fw-bold mb-1">Acción</label>
                                    <select id="filtroAccion" class="form-select form-select-sm">
                                        <option value="">Todas las acciones</option>
                                        <?php foreach ($accionesAuditoria as $a): ?>
                                        <option value="<?php echo htmlspecialchars($a); ?>"><?php echo htmlspecialchars($a); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button id="btnFiltrar" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
                                </div>
                                <div class="col-md-2">
                                    <button id="btnLimpiarFiltros" class="btn btn-sm btn-outline-secondary w-100">Limpiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <?php if (\Core\Auth::canExportar()): ?>
                        <button class="btn btn-success" id="btnExportAcciones" style="background: var(--accent-color, #27ae60); border: none;">
                            <i class="fa-solid fa-file-excel me-2"></i> Exportar Acciones a Excel
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="accionesTable" class="table table-striped align-middle w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha y Hora</th>
                                            <th>Usuario</th>
                                            <th>Módulo</th>
                                            <th>Acción</th>
                                            <th>Detalles</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Errores -->
                <div class="tab-pane fade" id="errores" role="tabpanel" aria-labelledby="errores-tab">
                    <div class="d-flex justify-content-end mb-3">
                        <?php if (\Core\Auth::canExportar()): ?>
                        <button class="btn btn-danger" id="btnExportErrores" style="border: none;">
                            <i class="fa-solid fa-file-excel me-2"></i> Exportar Errores a Excel
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card border-0 shadow-sm border-danger">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="erroresTable" class="table table-striped align-middle w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha y Hora</th>
                                            <th>Usuario</th>
                                            <th>Mensaje de Error</th>
                                            <th>Archivo / Línea</th>
                                            <th>URL</th>
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

<!-- Modal Stack Trace -->
<div class="modal fade" id="stackTraceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-bug"></i> Detalles del Error</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold">Mensaje:</h6>
                <p id="st_mensaje" class="text-danger"></p>
                <h6 id="st_original_label" class="fw-bold" style="display: none;">Mensaje técnico original:</h6>
                <p id="st_mensaje_original" class="text-muted small" style="display: none; word-break: break-word;"></p>
                <h6 class="fw-bold">Ubicación:</h6>
                <p id="st_archivo"></p>
                <h6 class="fw-bold">Stack Trace:</h6>
                <div id="st_trace" class="stack-trace"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="../assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="../assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>
<script src="../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Cargar Notificaciones

    // Init DataTables
    const accionesTable = $('#accionesTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api/auditoria_data.php",
            "data": function(d) {
                d.filtro_modulo = $('#filtroModulo').val();
                d.filtro_accion = $('#filtroAccion').val();
            }
        },
        
        "columns": [
            { "data": "id" },
            { "data": "fecha_hora" },
            { "data": "usuario" },
            { "data": "modulo" },
            { 
                "data": "accion",
                "render": function(data, type, row) {
                    let badgeClass = 'bg-secondary';
                    if (data === 'CREAR') badgeClass = 'bg-success';
                    if (data === 'EDITAR') badgeClass = 'bg-warning text-dark';
                    if (data === 'ELIMINAR') badgeClass = 'bg-danger';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { "data": "detalles" },
            { "data": "ip_address" }
        ],
        "order": [[0, "desc"]]
    });

    $('#btnFiltrar').on('click', function() {
        accionesTable.ajax.reload();
    });
    $('#btnLimpiarFiltros').on('click', function() {
        $('#filtroModulo').val('');
        $('#filtroAccion').val('');
        accionesTable.ajax.reload();
    });

    const erroresTable = $('#erroresTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "api/errores_data.php",
        
        "columns": [
            { "data": "id" },
            { "data": "fecha_hora" },
            { "data": "usuario" },
            { 
                "data": "mensaje",
                "render": function(data, type, row) {
                    return `<span class="text-danger fw-bold">${data.length > 50 ? data.substring(0, 50) + '...' : data}</span>`;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `<small class="text-muted">${row.archivo || 'N/A'} : ${row.linea || '-'}</small>`;
                }
            },
            { "data": "url" },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-outline-danger view-trace-btn" 
                                data-mensaje="${row.mensaje.replace(/"/g, '&quot;')}"
                                data-archivo="${row.archivo || 'N/A'} : ${row.linea || '-'}"
                                data-trace="${(row.stack_trace || 'Sin stack trace').replace(/"/g, '&quot;')}">
                                <i class="fa-solid fa-eye"></i> Detalles
                            </button>`;
                }
            }
        ],
        "order": [[0, "desc"]]
    });

    // View Trace Modal
    $('#erroresTable').on('click', '.view-trace-btn', function() {
        $('#st_mensaje').text($(this).data('mensaje'));
        $('#st_archivo').text($(this).data('archivo'));
        let trace = $(this).data('trace') || '';
        const marker = '[MENSAJE ORIGINAL]';
        const pos = trace.lastIndexOf(marker);
        if (pos >= 0) {
            const original = trace.substring(pos + marker.length).trim();
            trace = trace.substring(0, pos).trim();
            $('#st_original_label').show();
            $('#st_mensaje_original').text(original).show();
        } else {
            $('#st_original_label').hide();
            $('#st_mensaje_original').hide();
        }
        $('#st_trace').text(trace || 'Sin detalles técnicos.');
        $('#stackTraceModal').modal('show');
    });

    // Handle tab switching for DataTables responsiveness
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // Exportar Acciones
    $('#btnExportAcciones').on('click', function() {
        const searchValue = accionesTable.search();
        window.exportToExcelAsync('api/export_auditoria.php', {
            search: searchValue,
            csrf_token: '<?php echo \Core\Auth::generateCSRF(); ?>'
        }, 'Exportando Bitácora de Auditoría');
    });

    // Exportar Errores
    $('#btnExportErrores').on('click', function() {
        const searchValue = erroresTable.search();
        window.exportToExcelAsync('api/export_errores.php', {
            search: searchValue,
            csrf_token: '<?php echo \Core\Auth::generateCSRF(); ?>'
        }, 'Exportando Bitácora de Errores');
    });
});
</script>

<script src="../assets/js/global.js"></script>
</body>
</html>
