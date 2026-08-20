<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_turnos');
\Core\Auth::check();

require_once '../../core/Database.php';
use Core\Database;

$pdo = Database::getReadConnection();
$enEspera = (int)$pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'EN_ESPERA'")->fetchColumn();
$atendiendo = $pdo->query("SELECT folio, modulo_atencion FROM turnos WHERE estado = 'ATENDIENDO' ORDER BY atendido_en DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$atendidosHoy = (int)$pdo->query("SELECT COUNT(*) FROM turnos WHERE estado IN ('COMPLETADO','CANCELADO') AND DATE(creado_en) = CURDATE()")->fetchColumn();

$modulos = ['ACTA DE NACIMIENTO', 'ACTA DE MATRIMONIO', 'ACTA FORÁNEA', 'CONSTANCIA / INEXISTENCIA', 'CURP', 'PETICIÓN / MESA DE AYUDA', 'OTRO TRÁMITE'];

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
    <title>Turnos de Atención - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/vendor/fontawesome/css/all.min.css">
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
                <button type="button" id="sidebarCollapse" class="btn btn-primary" style="background: var(--primary-color); border: none;">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-flex align-items-center ms-auto">
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
                            <li class="p-4 text-center text-muted" id="notifEmpty">Sin novedades recientes.</li>
                        </ul>
                    </div>
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
                <h2><i class="fa-solid fa-list-ol text-primary me-2"></i> Turnos de Atención</h2>
                <div class="d-flex gap-2">
                    <a href="../../public/turnos.php" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-tv"></i> Ver pantalla pública
                    </a>
                    <button class="btn btn-primary" id="btnNuevoTurno" style="background: var(--secondary-color); border: none;">
                        <i class="fa-solid fa-plus"></i> Generar Turno
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="card-body">
                            <h1 class="display-5 fw-bold text-warning" id="statEspera"><?php echo $enEspera; ?></h1>
                            <span class="text-muted">Turnos en espera</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="card-body">
                            <h1 class="display-5 fw-bold text-success" id="statAtendiendo"><?php echo htmlspecialchars($atendiendo['folio'] ?? '---'); ?></h1>
                            <span class="text-muted"><?php echo htmlspecialchars($atendiendo['modulo_atencion'] ?? 'Nadie en atención'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="card-body">
                            <h1 class="display-5 fw-bold text-primary"><?php echo $atendidosHoy; ?></h1>
                            <span class="text-muted">Atendidos hoy</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fa-solid fa-people-line me-2 text-primary"></i> Cola de atención
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="turnosTable" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Módulo</th>
                                    <th>Ciudadano</th>
                                    <th>Estado</th>
                                    <th>Ventanilla</th>
                                    <th>Capturado</th>
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

<!-- Modal Nuevo Turno -->
<div class="modal fade" id="nuevoTurnoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-ticket me-2"></i> Generar Turno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modulo_atencion" class="form-label fw-bold">Módulo de Atención</label>
                    <select class="form-select" id="modulo_atencion" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($modulos as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="ciudadano_nombre" class="form-label fw-bold">Nombre del Ciudadano (opcional)</label>
                    <input type="text" class="form-control text-uppercase-input" id="ciudadano_nombre" maxlength="250"
                           placeholder="SOLO SI SE SOLICITA PARA ALGUIEN MÁS">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarTurno" style="background: var(--secondary-color); border: none;">
                    <i class="fa-solid fa-check"></i> Generar e Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = '<?php echo \Core\Auth::generateCSRF(); ?>';

    $(document).on('input', '.text-uppercase-input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    const estadoBadge = {
        'EN_ESPERA': ['bg-warning text-dark', 'EN ESPERA'],
        'ATENDIENDO': ['bg-info text-dark', 'EN ATENCIÓN'],
        'COMPLETADO': ['bg-success', 'COMPLETADO'],
        'CANCELADO': ['bg-danger', 'CANCELADO']
    };

    function cargarTablero() {
        $.ajax({
            url: 'data.php',
            type: 'GET',
            dataType: 'json',
            success: function(r) {
                if (r.status !== 'success') return;
                $('#statEspera').text(r.en_espera);
                $('#statAtendiendo').text(r.atendiendo_folio || '---');
                $('.statAtendiendoModulo').remove();
                $('#statAtendiendo').parent().append(`<div class="text-muted small statAtendiendoModulo">${r.atendiendo_modulo || 'Nadie en atención'}</div>`);
                const tbody = $('#turnosTable tbody');
                tbody.empty();
                r.turnos.forEach(function(t) {
                    const badge = estadoBadge[t.estado] || ['bg-secondary', t.estado];
                    let acciones = '';
                    if (t.estado === 'EN_ESPERA') {
                        acciones += `<button class="btn btn-sm btn-outline-info btn-atender me-1" data-id="${t.id}" title="Iniciar atención"><i class="fa-solid fa-hand-pointer"></i> Atender</button>`;
                    }
                    if (t.estado === 'ATENDIENDO') {
                        acciones += `<button class="btn btn-sm btn-outline-success btn-completar me-1" data-id="${t.id}" title="Finalizar"><i class="fa-solid fa-check"></i> Finalizar</button>`;
                    }
                    if (t.estado === 'EN_ESPERA' || t.estado === 'ATENDIENDO') {
                        acciones += `<button class="btn btn-sm btn-outline-danger btn-cancelar me-1" data-id="${t.id}" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>`;
                    }
                    acciones += `<button class="btn btn-sm btn-outline-primary btn-ticket" data-id="${t.id}" title="Imprimir ticket"><i class="fa-solid fa-print"></i></button>`;
                    tbody.append(`<tr>
                        <td class="fw-bold">${t.folio}</td>
                        <td>${t.modulo_atencion}</td>
                        <td>${t.ciudadano_nombre || '<span class="text-muted">SIN NOMBRE</span>'}</td>
                        <td><span class="badge ${badge[0]}">${badge[1]}</span></td>
                        <td>${t.ventanilla || '—'}</td>
                        <td>${t.creado_en}</td>
                        <td>${acciones}</td>
                    </tr>`);
                });
            }
        });
    }
    cargarTablero();
    setInterval(cargarTablero, 15000);

    $('#btnNuevoTurno').on('click', function() {
        $('#modulo_atencion').val('');
        $('#ciudadano_nombre').val('');
        $('#nuevoTurnoModal').modal('show');
    });

    $('#btnGuardarTurno').on('click', function() {
        const modulo = $('#modulo_atencion').val();
        if (!modulo) {
            Swal.fire({ icon: 'warning', title: 'Seleccione el módulo', confirmButtonColor: 'var(--primary-color)' });
            return;
        }
        $.ajax({
            url: 'crear.php',
            type: 'POST',
            data: { modulo_atencion: modulo, ciudadano_nombre: $('#ciudadano_nombre').val(), csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                $('#nuevoTurnoModal').modal('hide');
                if (response.status === 'success') {
                    window.open('ticket.php?id=' + response.id, '_blank');
                    cargarTablero();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    });

    function cambiarEstado(id, estado, msg) {
        $.ajax({
            url: 'estado.php',
            type: 'POST',
            data: { id: id, estado: estado, csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (msg) Swal.fire({ icon: 'success', title: msg, confirmButtonColor: 'var(--secondary-color)' });
                    cargarTablero();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    }

    $('#turnosTable').on('click', '.btn-atender', function() {
        cambiarEstado($(this).data('id'), 'ATENDIENDO', 'Turno en atención.');
    });
    $('#turnosTable').on('click', '.btn-completar', function() {
        cambiarEstado($(this).data('id'), 'COMPLETADO', 'Turno finalizado.');
    });
    $('#turnosTable').on('click', '.btn-cancelar', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Cancelar este turno?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No',
            confirmButtonColor: 'var(--primary-color)'
        }).then((result) => {
            if (result.isConfirmed) cambiarEstado(id, 'CANCELADO', null);
        });
    });
    $('#turnosTable').on('click', '.btn-ticket', function() {
        window.open('ticket.php?id=' + $(this).data('id'), '_blank');
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
