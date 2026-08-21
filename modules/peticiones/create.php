<?php
require_once '../../core/Auth.php';
\Core\Auth::checkPermission('permiso_tickets');
\Core\Auth::check();

$current_module = basename(dirname($_SERVER['SCRIPT_NAME']));
$path_prefix = ($current_module == 'public') ? '../modules/' : '../';
$db_link = ($current_module == 'public') ? 'index.php' : '../../public/index.php';
$logout_link = ($current_module == 'public') ? 'logout.php' : '../../public/logout.php';
$profile_link = ($current_module == 'public') ? 'perfil.php' : '../../public/perfil.php';
$notif_api = ($current_module == 'public') ? 'api/notifications.php' : '../../public/api/notifications.php';

// ParÃ¡metros opcionales de escalamiento desde Ventanilla RÃ¡pida
$escalado_ciudadano_id = intval($_GET['ciudadano_id'] ?? 0);
$escalado_solicitante = htmlspecialchars(trim($_GET['solicitante'] ?? ''));
$escalado_curp = htmlspecialchars(trim($_GET['curp'] ?? ''));
$escalado_tipo = htmlspecialchars(trim($_GET['tipo'] ?? ''));
$escalado_detalle = htmlspecialchars(trim($_GET['detalle'] ?? ''));
$escalado_folio = htmlspecialchars(trim($_GET['folio_origen'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apertura de Expediente - Ventanilla de Seguimiento</title>
    <link href="../../assets/css/fonts.css" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/vendor/fontawesome/css/all.min.css">
    <link href="../../assets/vendor/tom-select/css/tom-select.bootstrap5.min.css" rel="stylesheet">
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
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-flex align-items-center ms-auto">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-dark d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo \Core\Utils::getAvatarHtml(\Core\Auth::getUserName(), 34); ?>
                            <span class="fw-semibold ms-1"><?php echo htmlspecialchars(\Core\Auth::getUserName()); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item py-2" href="<?php echo $profile_link; ?>"><i class="fa-solid fa-user fa-sm me-2 text-muted"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $logout_link; ?>"><i class="fa-solid fa-right-from-bracket fa-sm me-2"></i> Cerrar SesiÃ³n</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-folder-plus text-primary me-2"></i> Apertura de Expediente</h2>
                    <p class="text-muted small mb-0">Ventanilla de Seguimiento â€” Casos especiales, dictÃ¡menes y trÃ¡mites de largo plazo</p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver a Expedientes
                </a>
            </div>

            <?php if (!empty($escalado_folio)): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center">
                <i class="fa-solid fa-link fa-lg me-3 text-info"></i>
                <div>
                    <strong>TrÃ¡mite escalado desde Ventanilla RÃ¡pida (Folio: <?php echo $escalado_folio; ?>)</strong>
                    <div class="small">Se han precargado los antecedentes para aperturar el expediente formal de seguimiento.</div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form id="formSeguimiento">
                        <input type="hidden" name="csrf_token" value="<?php echo \Core\Auth::generateCSRF(); ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="ciudadano_id" class="form-label fw-bold">
                                    Ciudadano Titular del Expediente <span class="text-danger">*</span>
                                </label>
                                <select id="ciudadano_id" name="ciudadano_id" required placeholder="Buscar por CURP o nombre en el padrÃ³n..."></select>
                                <div class="form-text">
                                    Â¿No estÃ¡ en el padrÃ³n? <a href="../ciudadanos/create.php" target="_blank" class="fw-semibold">Registrar ciudadano en padrÃ³n</a>.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="tipo_peticion" class="form-label fw-bold">
                                    Materia / Tipo de Caso <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="tipo_peticion" name="tipo_peticion" required>
                                    <option value="">Seleccione la materia del caso...</option>
                                    <option value="CORRECCION_ACTA">CORRECCIÃ“N ADMINISTRATIVA DE ACTA</option>
                                    <option value="ACLARACION">ACLARACIÃ“N JURÃDICA / COTEJO DE LIBRO</option>
                                    <option value="DIGITALIZACION">DIGITALIZACIÃ“N / ALTA EN BASE NACIONAL (RENAPO/SIDEC)</option>
                                    <option value="REGISTRO_EXTEMPORANEO">SOLICITUD DE REGISTRO EXTEMPORÃNEO</option>
                                    <option value="IDENTIDAD_GENERO">RECONOCIMIENTO DE IDENTIDAD DE GÃ‰NERO</option>
                                    <option value="OFICIO_DIR_GENERAL">TRÃMITE TURNADO A DIRECCIÃ“N GENERAL</option>
                                    <option value="OTRO">OTRO TRÃMITE ESPECIAL EN SEGUIMIENTO</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="descripcion" class="form-label fw-bold">
                                    Antecedentes, Motivo del TrÃ¡mite y Dictamen Requerido <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control text-uppercase-input" id="descripcion" name="descripcion" rows="5" 
                                          placeholder="EJ: SE RECIBE SOLICITUD DE RECTIFICACIÃ“N POR ERROR EN EL APELLIDO PATERNO DEL REGISTRADO EN EL LIBRO 2 ACTA 45 DEL AÃ‘O 1980. SE TURNA A REVISIÃ“N DE ARCHIVO..." required><?php echo !empty($escalado_detalle) ? htmlspecialchars("TRÃMITE ESCALADO DESDE VENTANILLA RÃPIDA [FOLIO {$escalado_folio}]: {$escalado_detalle}") : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="index.php" class="btn btn-secondary px-3">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 py-2" id="btnSubmit">
                                <i class="fa-solid fa-folder-plus me-1"></i> Aperturar Expediente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/tom-select/js/tom-select.complete.min.js"></script>
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    $(document).on('input', '.text-uppercase-input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    const tomCiudadano = new TomSelect("#ciudadano_id", {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        maxItems: 1,
        load: function(query, callback) {
            if (!query || query.length < 2) return callback();
            $.ajax({
                url: '../ciudadanos/search.php',
                type: 'GET',
                dataType: 'json',
                data: { q: query },
                error: function() { callback(); },
                success: function(res) { callback(res.results); }
            });
        },
        placeholder: 'Buscar ciudadano por CURP o Nombre...',
        allowEmptyOption: false
    });

    // Si viene prellenado un ciudadano_id
    <?php if ($escalado_ciudadano_id > 0): ?>
    tomCiudadano.addOption({ id: <?php echo $escalado_ciudadano_id; ?>, text: '<?php echo $escalado_solicitante . (!empty($escalado_curp) ? " - {$escalado_curp}" : ""); ?>' });
    tomCiudadano.setValue(<?php echo $escalado_ciudadano_id; ?>);
    <?php endif; ?>

    $('#formSeguimiento').on('submit', function(e) {
        e.preventDefault();

        const cid = $('#ciudadano_id').val();
        const tipo = $('#tipo_peticion').val();
        const desc = $('#descripcion').val().trim();

        if (!cid) {
            Swal.fire({ icon: 'warning', title: 'Ciudadano requerido', text: 'Seleccione al ciudadano titular del expediente.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (!tipo) {
            Swal.fire({ icon: 'warning', title: 'Materia requerida', text: 'Seleccione el tipo de caso o materia del expediente.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (desc.length < 10) {
            Swal.fire({ icon: 'warning', title: 'DescripciÃ³n detallada', text: 'Escriba una descripciÃ³n de antecedentes de al menos 10 caracteres.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        const $btn = $('#btnSubmit');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Aperturando...');

        $.ajax({
            url: 'save.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-folder-plus me-1"></i> Aperturar Expediente');
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Expediente Aperturado',
                        html: `Folio de seguimiento: <strong class="text-primary fs-4">${response.folio}</strong>`,
                        confirmButtonText: '<i class="fa-solid fa-folder-open me-1"></i> Ir a Ventanilla de Seguimiento',
                        confirmButtonColor: 'var(--secondary-color)'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-folder-plus me-1"></i> Aperturar Expediente');
                Swal.fire({ icon: 'error', title: 'Error de ConexiÃ³n', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
