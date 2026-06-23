<?php
require_once '../../vendor/autoload.php';
require_once '../../core/Auth.php';
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
    <title>Inexistencias - ERP DRC</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
        <!-- Sidebar -->
        <!-- Sidebar -->
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
                <h2>Listado de Constancias</h2>
                <div>
                    <button class="btn btn-success me-2" id="btnExportExcel">
                        <i class="fa-solid fa-file-excel"></i> Exportar a Excel
                    </button>
                    <button class="btn btn-primary" style="background: var(--secondary-color); border: none;" data-bs-toggle="modal" data-bs-target="#createInexistenciaModal">
                        <i class="fa-solid fa-plus"></i> Nuevo Registro
                    </button>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-white fw-bold">
                    Filtros de Búsqueda
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="filter_tipo" class="form-label fw-bold">Tipo de Constancia</label>
                             <select class="form-select" id="filter_tipo">
                                 <option value="">TODAS LAS CONSTANCIAS</option>
                                 <?php
                                 $opciones = \Core\Catalogo::getOpciones('tipo_constancia');
                                 foreach ($opciones as $opc) {
                                     // Let's format the display text to be similar to the original hardcoded ones or keep the catalog value
                                     // Original has "INEXISTENCIA DE NACIMIENTO" instead of "CONSTANCIA DE INEXISTENCIA DE NACIMIENTO"
                                     // But since they can add new ones, we can just display the catalog value (which is clean and accurate)
                                     $label = str_replace('CONSTANCIA DE ', '', $opc['valor']);
                                     echo '<option value="' . htmlspecialchars($opc['clave'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                                 }
                                 ?>
                             </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
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
                        <tbody class="table-skeleton">
                            <tr>
                                <td><span class="skeleton" style="width: 81%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 77%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 74%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 61%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 81%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 93%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 68%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 93%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 92%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 74%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 82%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 63%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 76%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 66%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 70%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 63%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 92%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 85%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 72%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 61%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 72%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 66%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 76%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 66%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 80%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 74%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 88%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 63%; height: 16px;"></span></td>
                            </tr>
                        </tbody>

                    </table>
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
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="modal_tipo_constancia" class="form-label fw-bold">Tipo de Constancia</label>
                            <select class="form-select" id="modal_tipo_constancia" name="tipo_constancia" required>
                                <option value="">Seleccione tipo...</option>
                                <?php
                                $opciones = \Core\Catalogo::getOpciones('tipo_constancia');
                                foreach ($opciones as $opc) {
                                    $label = str_replace('CONSTANCIA DE ', '', $opc['valor']);
                                    echo '<option value="' . htmlspecialchars($opc['clave'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

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

        const table = $('#inexistenciasTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "data.php",
                "data": function(d) {
                    d.tipo_constancia = $('#filter_tipo').val();
                }
            },
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
            },
            "columns": [
                { "data": "id" },
                { 
                    "data": "tipo_constancia",
                    "render": function(data) {
                        const types = {
                            'INEXISTENCIA_NACIMIENTO': 'INEXISTENCIA NACIMIENTO',
                            'INEXISTENCIA_MATRIMONIO': 'INEXISTENCIA MATRIMONIO',
                            'INEXISTENCIA_DESCENDENCIA': 'INEXISTENCIA DESCENDENCIA',
                            'NO_DEUDOR': 'NO DEUDOR ALIMENTARIO'
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
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: 'export_excel.php',
                type: 'GET',
                data: { tipo: tipo },
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
                        text: 'No se pudo conectar con el servidor para procesar la cola de exportación.',
                        confirmButtonColor: 'var(--primary-color)'
                    });
                }
            });
        });

        // Calcular fecha de llegada automáticamente en el modal (Trámite + 15 días)
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

        // Envío AJAX del formulario del Modal
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
                        // Close Modal
                        const modalEl = document.getElementById('createInexistenciaModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        $form[0].reset();
                        
                        // Reload Table
                        table.ajax.reload(null, false);
                        
                        // Show SweetAlert Toast
                        window.showToast('success', '¡Guardado!', 'El registro se ha guardado exitosamente.');
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
    });
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
