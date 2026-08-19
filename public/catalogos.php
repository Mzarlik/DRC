<?php
// public/catalogos.php
require_once '../vendor/autoload.php';
require_once '../core/Auth.php';
\Core\Auth::check();

if (!in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'COORDINADOR', 'SUPERVISOR'])) {
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
    <title>Gestión de Catálogos y Conceptos - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">
    <link href="../assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <li class="<?php echo ($current_module == 'peticion_rapida' && basename($_SERVER['PHP_SELF']) != 'reporte_diario.php') ? 'active' : ''; ?>">
                        <a href="<?php echo ($current_module == 'peticion_rapida') ? 'index.php' : $path_prefix . 'peticion_rapida/index.php'; ?>">
                            <i class="fa-solid fa-bolt text-warning"></i> <span class="sidebar-text">Petición Rápida</span>
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
                            <i class="fa-solid fa-folder-open text-primary"></i> <span class="sidebar-text">Ventanilla de Seguimiento</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Administración (Admin / Supervisor) -->
            <?php if (in_array($_SESSION['user_rol'] ?? '', ['ADMIN', 'COORDINADOR', 'SUPERVISOR'])): ?>
            <li class="<?php echo ($current_module == 'public' && (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'auditoria.php' || basename($_SERVER['PHP_SELF']) == 'catalogos.php')) ? 'active' : ''; ?>">
                <a href="#adminSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
                    <i class="fa-solid fa-users-gear"></i> <span class="sidebar-text">Administración</span>
                </a>
                <ul class="collapse list-unstyled show" id="adminSubmenu">
                    <?php if (($_SESSION['user_rol'] ?? '') === 'ADMIN'): ?>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'usuarios.php') ? 'active' : ''; ?>"><a href="usuarios.php"><i class="fa-solid fa-user-shield"></i> <span class="sidebar-text">Usuarios y Permisos</span></a></li>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'auditoria.php') ? 'active' : ''; ?>"><a href="auditoria.php"><i class="fa-solid fa-clipboard-list"></i> <span class="sidebar-text">Auditoría y Errores</span></a></li>
                    <?php endif; ?>
                    <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'catalogos.php') ? 'active' : ''; ?>"><a href="catalogos.php"><i class="fa-solid fa-gears"></i> <span class="sidebar-text">Conceptos y Catálogos</span></a></li>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0 text-gray-800">Conceptos y Catálogos del Sistema</h2>
                <button class="btn btn-primary" id="btnNuevoConcepto" style="background: var(--primary-color); border: none;">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo Concepto
                </button>
            </div>

            <!-- Selector de Catálogo -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label for="selectCatalogo" class="form-label fw-bold">Seleccionar Catálogo a Administrar</label>
                            <select class="form-select" id="selectCatalogo">
                                <option value="tipo_constancia" selected>Tipos de Constancias de Inexistencia</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <span class="text-muted small">Agrega nuevos conceptos para desplegarlos dinámicamente en los formularios correspondientes.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Opciones -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="opcionesTable" class="table table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Clave de Registro</th>
                                    <th>Concepto / Descripción</th>
                                    <th>Estado</th>
                                    <th>Orden</th>
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

<!-- Modal Nuevo Concepto -->
<div class="modal fade" id="conceptoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="formConcepto">
                <input type="hidden" name="action" value="agregar_opcion">
                <input type="hidden" name="catalogo" id="modalCatalogo" value="tipo_constancia">
                <input type="hidden" name="csrf_token" value="<?php echo \Core\Auth::generateCSRF(); ?>">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="fa-solid fa-plus me-2"></i> Registrar Nuevo Concepto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="opcClave" class="form-label fw-bold">Clave del Concepto (Mayúsculas sin espacios)</label>
                        <input type="text" class="form-control text-uppercase" id="opcClave" name="clave" required placeholder="Ej. INEXISTENCIA_DIVORCIO">
                        <div class="form-text">Se reemplazan los espacios por guiones bajos (_). Solo letras y números.</div>
                    </div>
                    <div class="mb-3">
                        <label for="opcValor" class="form-label fw-bold">Nombre / Descripción Comercial</label>
                        <input type="text" class="form-control" id="opcValor" name="valor" required placeholder="Ej. CONSTANCIA DE INEXISTENCIA DE DIVORCIO">
                        <div class="form-text">Texto exacto que el usuario visualizará en la lista desplegable.</div>
                    </div>
                    <div class="mb-3">
                        <label for="opcOrden" class="form-label fw-bold">Orden de Visualización</label>
                        <input type="number" class="form-control" id="opcOrden" name="orden" value="0" min="0">
                        <div class="form-text">Define la prioridad en la lista. Cero (0) se ordenará alfabéticamente.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--primary-color); border: none;">Guardar Concepto</button>
                </div>
            </form>
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

    // Inicializar DataTable
    let currentCatalog = $('#selectCatalogo').val();
    
    const opcionesTable = $('#opcionesTable').DataTable({
        "ajax": {
            "url": "api/catalogos_handler.php",
            "type": "GET",
            "data": function(d) {
                d.action = 'get_opciones';
                d.catalogo = currentCatalog;
            }
        },
        
        "columns": [
            { "data": "id" },
            { 
                "data": "clave",
                "render": function(data) {
                    return `<code class="fw-bold">${data}</code>`;
                }
            },
            { "data": "valor" },
            { 
                "data": "activo",
                "render": function(data) {
                    if (data == 1) {
                        return '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Activo</span>';
                    } else {
                        return '<span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i> Inactivo</span>';
                    }
                }
            },
            { "data": "orden" },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    const btnClass = row.activo == 1 ? 'btn-outline-danger' : 'btn-outline-success';
                    const btnIcon = row.activo == 1 ? 'fa-eye-slash' : 'fa-eye';
                    const btnText = row.activo == 1 ? 'Desactivar' : 'Activar';
                    
                    return `
                        <button class="btn btn-sm ${btnClass} toggle-status-btn" data-id="${row.id}" data-activo="${row.activo == 1 ? 0 : 1}">
                            <i class="fa-solid ${btnIcon} me-1"></i> ${btnText}
                        </button>
                    `;
                }
            }
        ]
    });

    // Cambiar de catálogo
    $('#selectCatalogo').on('change', function() {
        currentCatalog = $(this).val();
        $('#modalCatalogo').val(currentCatalog);
        opcionesTable.ajax.reload();
    });

    // Mostrar modal nuevo concepto
    $('#btnNuevoConcepto').on('click', function() {
        $('#formConcepto')[0].reset();
        $('#conceptoModal').modal('show');
    });

    // Guardar concepto
    $('#formConcepto').on('submit', function(e) {
        e.preventDefault();
        
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Guardando...');
        
        $.ajax({
            url: 'api/catalogos_handler.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Guardar Concepto');
                if (response.status === 'success') {
                    $('#conceptoModal').modal('hide');
                    opcionesTable.ajax.reload();
                    window.showToast('success', 'Éxito', response.message);
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
                $submitBtn.prop('disabled', false).text('Guardar Concepto');
                Swal.fire({
                    icon: 'error',
                    title: 'Error Crítico',
                    text: 'No se pudo conectar con el servidor para registrar el concepto.',
                    confirmButtonColor: 'var(--primary-color)'
                });
            }
        });
    });

    // Toggle activo/inactivo
    $('#opcionesTable').on('click', '.toggle-status-btn', function() {
        const id = $(this).data('id');
        const activo = $(this).data('activo');
        const actionText = activo == 1 ? 'activar' : 'desactivar';
        
        Swal.fire({
            title: '¿Confirmar acción?',
            text: `¿Estás seguro de que deseas ${actionText} esta opción del catálogo?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary-color)',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/catalogos_handler.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_estado',
                        id: id,
                        activo: activo,
                        csrf_token: $('#formConcepto input[name="csrf_token"]').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            opcionesTable.ajax.reload();
                            window.showToast('success', 'Completado', response.message);
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Crítico',
                            text: 'No se pudo conectar con el servidor.',
                            confirmButtonColor: 'var(--primary-color)'
                        });
                    }
                });
            }
        });
    });

    // Sidebar collapse
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar, #content').toggleClass('active');
        $('.collapse.in').toggleClass('in');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    });
    
    $('#sidebarCloseMobile').on('click', function() {
        $('#sidebar, #content').removeClass('active');
    });
});
</script>

<script src="../assets/js/global.js"></script>
</body>
</html>
