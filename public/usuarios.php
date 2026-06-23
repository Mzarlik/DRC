<?php
// public/usuarios.php
require_once '../core/Auth.php';
\Core\Auth::check();

// Only allow ADMIN role
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

require_once '../core/Database.php';
use Core\Database;

try {
    $pdo = Database::getConnection();
    // Fetch all users
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error de base de datos.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
</head>
<body>

<div class="wrapper">
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
                <h2>Administrar Usuarios y Permisos</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" id="btnExportExcel" style="background: var(--accent-color, #27ae60); border: none;">
                        <i class="fa-solid fa-file-excel"></i> Exportar consulta a Excel
                    </button>
                    <button class="btn btn-primary" style="background: var(--secondary-color); border: none;" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fa-solid fa-user-plus me-2"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($u['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['rol'] === 'ADMIN' ? 'danger' : ($u['rol'] === 'SUPERVISOR' ? 'info' : 'success'); ?>">
                                            <?php echo htmlspecialchars($u['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['estatus'] == 1 ? 'success' : 'secondary'; ?>">
                                            <?php echo $u['estatus'] == 1 ? 'ACTIVO' : 'INACTIVO'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-perms-btn" 
                                                data-id="<?php echo $u['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($u['nombre']); ?>"
                                                data-rol="<?php echo $u['rol']; ?>"
                                                data-estatus="<?php echo $u['estatus']; ?>"
                                                data-nacimientos="<?php echo $u['permiso_registro_nacimientos']; ?>"
                                                data-matrimonios="<?php echo $u['permiso_registro_matrimonios']; ?>"
                                                data-divorcios="<?php echo $u['permiso_registro_divorcios']; ?>"
                                                data-defunciones="<?php echo $u['permiso_registro_defunciones']; ?>"
                                                data-inscripciones="<?php echo $u['permiso_registro_inscripciones']; ?>"
                                                data-reconocimientos="<?php echo $u['permiso_registro_reconocimientos']; ?>"
                                                data-actas_locales="<?php echo $u['permiso_actas_locales']; ?>"
                                                data-actas_foraneas="<?php echo $u['permiso_actas_foraneas']; ?>"
                                                data-constancias="<?php echo $u['permiso_constancias']; ?>"
                                                data-curp="<?php echo $u['permiso_curp']; ?>"
                                                data-tickets="<?php echo $u['permiso_tickets']; ?>">
                                            <i class="fa-solid fa-user-gear"></i> Permisos
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create User -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCreateUser">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_nombre" class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" class="form-control" id="new_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_correo" class="form-label fw-bold">Correo Electrónico</label>
                        <input type="email" class="form-control" id="new_correo" name="correo" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-bold">Contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_rol" class="form-label fw-bold">Rol</label>
                        <select class="form-select" id="new_rol" name="rol" required>
                            <option value="OPERADOR">OPERADOR</option>
                            <option value="SUPERVISOR">SUPERVISOR</option>
                            <option value="ADMIN">ADMIN (Acceso Total)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--secondary-color); border: none;">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Permissions -->
<div class="modal fade" id="editPermsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditPerms">
                <input type="hidden" name="action" value="update_perms">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Configurar Permisos de: <span id="edit_nombre_label" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="edit_rol" class="form-label fw-bold">Rol</label>
                            <select class="form-select" id="edit_rol" name="rol" required>
                                <option value="OPERADOR">OPERADOR</option>
                                <option value="SUPERVISOR">SUPERVISOR</option>
                                <option value="ADMIN">ADMIN</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_estatus" class="form-label fw-bold">Estatus</label>
                            <select class="form-select" id="edit_estatus" name="estatus" required>
                                <option value="1">ACTIVO</option>
                                <option value="0">INACTIVO</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="alert alert-info py-2" id="adminWarning" style="display: none;">
                        <i class="fa-solid fa-circle-info me-2"></i> Los usuarios <strong>ADMIN</strong> siempre tienen acceso a todos los módulos independientemente de estas casillas.
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3">Permisos de Módulo (Operadores)</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_nacimientos" name="permiso_registro_nacimientos" value="1">
                                <label class="form-check-label fw-bold" for="p_nacimientos">Registrar Nacimientos</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_matrimonios" name="permiso_registro_matrimonios" value="1">
                                <label class="form-check-label fw-bold" for="p_matrimonios">Registrar Matrimonios</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_divorcios" name="permiso_registro_divorcios" value="1">
                                <label class="form-check-label fw-bold" for="p_divorcios">Registrar Divorcios</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_defunciones" name="permiso_registro_defunciones" value="1">
                                <label class="form-check-label fw-bold" for="p_defunciones">Registrar Defunciones</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_inscripciones" name="permiso_registro_inscripciones" value="1">
                                <label class="form-check-label fw-bold" for="p_inscripciones">Registrar Inscripciones</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_reconocimientos" name="permiso_registro_reconocimientos" value="1">
                                <label class="form-check-label fw-bold" for="p_reconocimientos">Registrar Reconocimientos</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_actas_locales" name="permiso_actas_locales" value="1">
                                <label class="form-check-label fw-bold" for="p_actas_locales">Expedir Actas Locales</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_actas_foraneas" name="permiso_actas_foraneas" value="1">
                                <label class="form-check-label fw-bold" for="p_actas_foraneas">Validar Actas Foráneas</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_constancias" name="permiso_constancias" value="1">
                                <label class="form-check-label fw-bold" for="p_constancias">Constancias de Inexistencia / No Deudor</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_curp" name="permiso_curp" value="1">
                                <label class="form-check-label fw-bold" for="p_curp">Trámites de CURP</label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="p_tickets" name="permiso_tickets" value="1">
                                <label class="form-check-label fw-bold" for="p_tickets">Mesa de Ayuda (Tickets)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--secondary-color); border: none;">Guardar Permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Sidebar Collapse

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

    // Opening Edit Modal and setting fields
    $('.edit-perms-btn').on('click', function() {
        let btn = $(this);
        $('#edit_id').val(btn.data('id'));
        $('#edit_nombre_label').text(btn.data('nombre'));
        $('#edit_rol').val(btn.data('rol'));
        $('#edit_estatus').val(btn.data('estatus'));

        // Set checkboxes
        $('#p_nacimientos').prop('checked', btn.data('nacimientos') == 1);
        $('#p_matrimonios').prop('checked', btn.data('matrimonios') == 1);
        $('#p_divorcios').prop('checked', btn.data('divorcios') == 1);
        $('#p_defunciones').prop('checked', btn.data('defunciones') == 1);
        $('#p_inscripciones').prop('checked', btn.data('inscripciones') == 1);
        $('#p_reconocimientos').prop('checked', btn.data('reconocimientos') == 1);
        $('#p_actas_locales').prop('checked', btn.data('actas_locales') == 1);
        $('#p_actas_foraneas').prop('checked', btn.data('actas_foraneas') == 1);
        $('#p_constancias').prop('checked', btn.data('constancias') == 1);
        $('#p_curp').prop('checked', btn.data('curp') == 1);
        $('#p_tickets').prop('checked', btn.data('tickets') == 1);

        toggleAdminWarning(btn.data('rol'));

        $('#editPermsModal').modal('show');
    });

    $('#edit_rol').on('change', function() {
        toggleAdminWarning($(this).val());
    });

    function toggleAdminWarning(rol) {
        if (rol === 'ADMIN') {
            $('#adminWarning').show();
        } else {
            $('#adminWarning').hide();
        }
    }

    // Submit Create User
    $('#formCreateUser').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'update_usuario.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = 'usuarios.php?toast=success&msg=' + encodeURIComponent('El usuario ha sido registrado exitosamente.');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: 'var(--primary-color)'
                    });
                }
            }
        });
    });

    // Submit Edit Perms
    $('#formEditPerms').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'update_usuario.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = 'usuarios.php?toast=success&msg=' + encodeURIComponent('Los permisos y datos han sido actualizados.');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: 'var(--primary-color)'
                    });
                }
            }
        });
    });

    // Exportar a Excel
    $('#btnExportExcel').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: 'api/export_usuarios.php',
            type: 'GET',
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
