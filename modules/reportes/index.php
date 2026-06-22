<?php
// modules/reportes/index.php
require_once '../../core/Auth.php';
\Core\Auth::check();

$current_module = 'reportes';
$path_prefix = '../../';
$db_link = '../../public/index.php';
$logout_link = '../../public/logout.php';
$profile_link = '../../public/perfil.php';
$notif_api = '../../public/api/notifications.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Cruzados - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
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
            <li>
                <a href="<?php echo $db_link; ?>"><i class="fa-solid fa-chart-line"></i> <span class="sidebar-text">Dashboard</span></a>
            </li>
            
            <li>
                <a href="#catSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-address-book"></i> <span class="sidebar-text">Catálogos</span>
                </a>
                <ul class="collapse list-unstyled" id="catSubmenu">
                    <li><a href="../ciudadanos/index.php"><i class="fa-solid fa-users"></i> <span class="sidebar-text">Ciudadanos</span></a></li>
                </ul>
            </li>

            <?php if (\Core\Auth::hasPermission('permiso_registro_nacimientos') || \Core\Auth::hasPermission('permiso_registro_matrimonios') || \Core\Auth::hasPermission('permiso_registro_divorcios') || \Core\Auth::hasPermission('permiso_registro_defunciones')): ?>
            <li>
                <a href="#vitalesSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-heart-pulse"></i> <span class="sidebar-text">Registro de Actos</span>
                </a>
                <ul class="collapse list-unstyled" id="vitalesSubmenu">
                    <?php if (\Core\Auth::hasPermission('permiso_registro_nacimientos')): ?>
                    <li><a href="../nacimientos/index.php"><i class="fa-solid fa-baby"></i> <span class="sidebar-text">Nacimientos</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_matrimonios')): ?>
                    <li><a href="../matrimonios/index.php"><i class="fa-solid fa-ring"></i> <span class="sidebar-text">Matrimonios</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_divorcios')): ?>
                    <li><a href="../divorcios/index.php"><i class="fa-solid fa-heart-crack"></i> <span class="sidebar-text">Divorcios</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_registro_defunciones')): ?>
                    <li><a href="../defunciones/index.php"><i class="fa-solid fa-book-skull"></i> <span class="sidebar-text">Defunciones</span></a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (\Core\Auth::hasPermission('permiso_actas_locales') || \Core\Auth::hasPermission('permiso_actas_foraneas')): ?>
            <li>
                <a href="#actasSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-print"></i> <span class="sidebar-text">Expedición de Actas</span>
                </a>
                <ul class="collapse list-unstyled" id="actasSubmenu">
                    <?php if (\Core\Auth::hasPermission('permiso_actas_locales')): ?>
                    <li><a href="../actas_locales/index.php"><i class="fa-solid fa-file-invoice"></i> <span class="sidebar-text">Actas Locales</span></a></li>
                    <?php endif; ?>
                    <?php if (\Core\Auth::hasPermission('permiso_actas_foraneas')): ?>
                    <li><a href="../foraneas/index.php"><i class="fa-solid fa-plane-arrival"></i> <span class="sidebar-text">Actas Foráneas</span></a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (\Core\Auth::hasPermission('permiso_constancias')): ?>
            <li>
                <a href="#constSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-file-signature"></i> <span class="sidebar-text">Constancias</span>
                </a>
                <ul class="collapse list-unstyled" id="constSubmenu">
                    <li><a href="../inexistencias/index.php"><i class="fa-solid fa-file-circle-exclamation"></i> <span class="sidebar-text">Inexistencias</span></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Módulo de Reportes Cruzados (Active) -->
            <li class="active">
                <a href="index.php"><i class="fa-solid fa-file-excel"></i> <span class="sidebar-text">Reportes Cruzados</span></a>
            </li>

            <?php if (($_SESSION['user_rol'] ?? '') === 'ADMIN'): ?>
            <li>
                <a href="../../public/usuarios.php"><i class="fa-solid fa-users-gear"></i> <span class="sidebar-text">Administración</span></a>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Generador de Reportes Cruzados</h2>
                <button class="btn btn-success" id="btnExportGeneralExcel">
                    <i class="fa-solid fa-file-excel"></i> Exportar Consulta a Excel
                </button>
            </div>
            
            <!-- Stackable Filters Card -->
            <div class="card mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="fa-solid fa-filter text-primary me-2"></i> Filtros de Búsqueda Apilables
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
                            <label for="filter_modulo" class="form-label fw-bold">Módulo</label>
                            <select class="form-select" id="filter_modulo">
                                <option value="">TODOS LOS MÓDULOS</option>
                                <option value="nacimientos">Nacimientos</option>
                                <option value="defunciones">Defunciones</option>
                                <option value="inexistencias">Inexistencias</option>
                                <option value="foraneas">Foráneas</option>
                                <option value="peticiones">Peticiones / Tickets</option>
                                <option value="matrimonios">Matrimonios</option>
                                <option value="divorcios">Divorcios</option>
                                <option value="reconocimientos">Reconocimientos</option>
                                <option value="inscripciones">Inscripciones</option>
                                <option value="tramites_curp">Trámites CURP</option>
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
                                <th>Módulo</th>
                                <th>ID / Folio / Acta</th>
                                <th>Referencia / Ciudadano</th>
                                <th>Fecha Registro</th>
                                <th>Operador / Capturista</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody class="table-skeleton">
                            <tr>
                                <td><span class="skeleton" style="width: 82%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 85%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 75%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 80%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 65%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 82%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 85%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 75%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 80%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 65%; height: 16px;"></span></td>
                            </tr>
                            <tr>
                                <td><span class="skeleton" style="width: 82%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 85%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 90%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 75%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 80%; height: 16px;"></span></td>
                                <td><span class="skeleton" style="width: 65%; height: 16px;"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
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
                badges.push({ id: 'modulo', label: `Módulo: ${moduloText}` });
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
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: 'export_excel.php',
                type: 'GET',
                data: {
                    fecha_inicio: $('#filter_fecha_inicio').val(),
                    fecha_fin: $('#filter_fecha_fin').val(),
                    modulo: $('#filter_modulo').val(),
                    estatus: $('#filter_estatus').val(),
                    operador_id: $('#filter_operador').val()
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Generando Reporte General',
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
                        title: 'Error de Red',
                        text: 'No se pudo conectar con el servidor para iniciar la generación asíncrona.',
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
