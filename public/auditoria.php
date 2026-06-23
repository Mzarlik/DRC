<?php
require_once '../core/Auth.php';
\Core\Auth::check();

if (($_SESSION['user_rol'] ?? '') !== 'ADMIN') {
    header("Location: index.php");
    exit;
}

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
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
            <li class="<?php echo ($current_module == 'public' && (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php')) ? 'active' : ''; ?>">
                <a href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fa-solid fa-users-gear"></i> <span class="sidebar-text">Administración</span>
                </a>
                <ul class="collapse list-unstyled <?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'show' : ''; ?>" id="adminSubmenu">
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>"><a href="usuarios.php"><i class="fa-solid fa-user-shield"></i> <span class="sidebar-text">Usuarios y Permisos</span></a></li>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'active' : ''; ?>"><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> <span class="sidebar-text">Auditoría y Errores</span></a></li>
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
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-success" id="btnExportAcciones" style="background: var(--accent-color, #27ae60); border: none;">
                            <i class="fa-solid fa-file-excel me-2"></i> Exportar Acciones a Excel
                        </button>
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
                        <button class="btn btn-danger" id="btnExportErrores" style="border: none;">
                            <i class="fa-solid fa-file-excel me-2"></i> Exportar Errores a Excel
                        </button>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
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

    // Init DataTables
    const accionesTable = $('#accionesTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "api/auditoria_data.php",
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
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

    const erroresTable = $('#erroresTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "api/errores_data.php",
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
        },
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
        $('#st_trace').text($(this).data('trace'));
        $('#stackTraceModal').modal('show');
    });

    // Handle tab switching for DataTables responsiveness
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // Exportar Acciones
    $('#btnExportAcciones').on('click', function() {
        const searchValue = accionesTable.search();
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: 'api/export_auditoria.php',
            type: 'GET',
            data: { search: searchValue },
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

    // Exportar Errores
    $('#btnExportErrores').on('click', function() {
        const searchValue = erroresTable.search();
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: 'api/export_errores.php',
            type: 'GET',
            data: { search: searchValue },
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
});
</script>

<script src="../assets/js/global.js"></script>
</body>
</html>
