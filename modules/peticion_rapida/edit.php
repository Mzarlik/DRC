<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

require_once __DIR__ . '/../../core/Database.php';
use Core\Database;
use Core\Services\PeticionRapidaService;

$id = intval($_GET['id'] ?? 0);
$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT * FROM peticiones_ventanilla WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    header("Location: index.php?toast=error&msg=" . urlencode("PeticiÃ³n no encontrada o eliminada"));
    exit;
}

// Descifrar CURP almacenada (retrocompatible con registros en texto plano)
if (!empty($pet['solicitante_curp'])) {
    $curpDescifrada = \Core\Encryption::decrypt($pet['solicitante_curp']);
    $pet['solicitante_curp'] = preg_match('/^[A-Z]{18}$/', $curpDescifrada) ? $curpDescifrada : $pet['solicitante_curp'];
}

$opciones = PeticionRapidaService::getCatalogoOrdenadoPorFrecuencia();

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
    <title>Editar PeticiÃ³n <?php echo htmlspecialchars($pet['folio']); ?> - ERP DRC</title>
    <link href="../../assets/css/fonts.css" rel="stylesheet">
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
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Editar PeticiÃ³n</h2>
                    <p class="text-muted small mb-0">Folio Oficial: <strong class="text-primary fs-5"><?php echo htmlspecialchars($pet['folio']); ?></strong></p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver al listado
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form id="formEditPv" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo \Core\Auth::generateCSRF(); ?>">
                        <input type="hidden" name="id" value="<?php echo $pet['id']; ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="solicitante_nombre" class="form-label fw-bold">
                                    Nombre Completo del Solicitante <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase-input" id="solicitante_nombre" name="solicitante_nombre" 
                                       value="<?php echo htmlspecialchars($pet['solicitante_nombre']); ?>" maxlength="150" required>
                            </div>
                            <div class="col-md-3">
                                <label for="solicitante_curp" class="form-label fw-bold">CURP (Opcional)</label>
                                <input type="text" class="form-control text-uppercase-input" id="solicitante_curp" name="solicitante_curp" maxlength="18"
                                       value="<?php echo htmlspecialchars($pet['solicitante_curp'] ?? ''); ?>" placeholder="18 caracteres">
                            </div>
                            <div class="col-md-3">
                                <label for="solicitante_telefono" class="form-label fw-bold">TelÃ©fono de Contacto</label>
                                <input type="tel" class="form-control" id="solicitante_telefono" name="solicitante_telefono" maxlength="10"
                                       value="<?php echo htmlspecialchars($pet['solicitante_telefono'] ?? ''); ?>" placeholder="10 dÃ­gitos">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="tipo_peticion" class="form-label fw-bold">
                                    Tipo de PeticiÃ³n / TrÃ¡mite <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="tipo_peticion" name="tipo_peticion" required>
                                    <?php foreach ($opciones as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['clave']); ?>" <?php echo ($pet['tipo_peticion'] === $op['clave']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($op['valor']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="estatus" class="form-label fw-bold">
                                    Estatus del TrÃ¡mite <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="estatus" name="estatus" required>
                                    <option value="PENDIENTE" <?php echo ($pet['estatus'] === 'PENDIENTE') ? 'selected' : ''; ?>>PENDIENTE (En ventanilla)</option>
                                    <option value="EN_PROCESO" <?php echo ($pet['estatus'] === 'EN_PROCESO') ? 'selected' : ''; ?>>EN PROCESO (BÃºsqueda / TrÃ¡mite)</option>
                                    <option value="ENTREGADO" <?php echo ($pet['estatus'] === 'ENTREGADO') ? 'selected' : ''; ?>>ENTREGADO (Finalizado)</option>
                                    <option value="CANCELADO" <?php echo ($pet['estatus'] === 'CANCELADO') ? 'selected' : ''; ?>>CANCELADO</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="detalle" class="form-label fw-bold">
                                    Detalle / Referencia del TrÃ¡mite <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase-input" id="detalle" name="detalle" maxlength="255"
                                       value="<?php echo htmlspecialchars($pet['detalle']); ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="index.php" class="btn btn-secondary px-3">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 py-2" id="btnSubmit">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios
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
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    const regexCurp = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/;

    $(document).on('input', '.text-uppercase-input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    $('#solicitante_telefono').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, '').slice(0, 10));
    });

    $('#formEditPv').on('submit', function(e) {
        e.preventDefault();

        const nombre = $('#solicitante_nombre').val().trim();
        const curp = $('#solicitante_curp').val().trim().toUpperCase();
        const tel = $('#solicitante_telefono').val().trim();
        const tipo = $('#tipo_peticion').val();
        const detalle = $('#detalle').val().trim();

        if (nombre.length < 3) {
            Swal.fire({ icon: 'warning', title: 'Nombre requerido', text: 'El nombre debe tener al menos 3 letras.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (curp.length > 0 && (!regexCurp.test(curp) || curp.length !== 18)) {
            Swal.fire({
                icon: 'warning',
                title: 'CURP no vÃ¡lida',
                html: `La CURP <strong>${curp}</strong> no cumple con el estÃ¡ndar de 18 caracteres de RENAPO.`,
                confirmButtonColor: 'var(--secondary-color)'
            });
            return;
        }

        if (tel.length > 0 && tel.length !== 10) {
            Swal.fire({ icon: 'warning', title: 'TelÃ©fono no vÃ¡lido', text: 'El telÃ©fono debe tener 10 dÃ­gitos.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (!tipo) {
            Swal.fire({ icon: 'warning', title: 'Seleccione trÃ¡mite', text: 'Seleccione un tipo de trÃ¡mite.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (detalle.length < 4) {
            Swal.fire({ icon: 'warning', title: 'Detalle requerido', text: 'Escriba el detalle de la peticiÃ³n.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        const $btn = $('#btnSubmit');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

        $.ajax({
            url: 'update.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios');
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: response.message,
                        confirmButtonText: 'Volver al listado',
                        confirmButtonColor: 'var(--secondary-color)'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios');
                Swal.fire({ icon: 'error', title: 'Error CrÃ­tico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
