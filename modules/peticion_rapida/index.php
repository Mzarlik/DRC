<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
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
    <title>Petición Rápida (Ventanilla) - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            <li><a href="<?php echo $db_link; ?>"><i class="fa-solid fa-chart-line"></i> <span class="sidebar-text">Dashboard</span></a></li>
            <li class="active"><a href="index.php"><i class="fa-solid fa-bolt text-warning"></i> <span class="sidebar-text">Petición Rápida</span></a></li>
            <li><a href="reporte_diario.php"><i class="fa-solid fa-file-invoice text-info"></i> <span class="sidebar-text">Reporte Diario</span></a></li>
            <li><a href="../peticiones/index.php"><i class="fa-solid fa-folder-open text-primary"></i> <span class="sidebar-text">Ventanilla de Seguimiento</span></a></li>
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
                            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $logout_link; ?>"><i class="fa-solid fa-right-from-bracket fa-sm me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-table-list text-primary me-2"></i> Control de Peticiones de Ventanilla</h2>
                    <p class="text-muted small mb-0">Historial, búsqueda y seguimiento de trámites en mostrador</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="reporte_diario.php" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-invoice me-1"></i> Reporte Diario Oficial
                    </a>
                    <a href="create.php" class="btn btn-warning text-dark fw-bold">
                        <i class="fa-solid fa-bolt me-1"></i> + Nueva Petición Rápida
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="pvTable" class="table table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Solicitante</th>
                                    <th>CURP / Contacto</th>
                                    <th>Tipo de Petición</th>
                                    <th>Detalle / Referencia</th>
                                    <th>Estatus</th>
                                    <th>Fecha</th>
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

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="../../assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="../../assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>
<script src="../../assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = '<?php echo \Core\Auth::generateCSRF(); ?>';

    const tramitesMap = <?php echo json_encode(array_map(fn($t) => "[{$t['codigo']}] {$t['nombre']}", \Core\Services\PeticionRapidaService::TRAMITES)); ?>;

    const pvTable = $('#pvTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "data.php",
        "columns": [
            { 
                "data": "folio",
                "render": function(data) {
                    return `<strong class="text-primary font-monospace">${data}</strong>`;
                }
            },
            { 
                "data": "solicitante_nombre",
                "render": function(data) {
                    return `<span class="fw-semibold">${data}</span>`;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    let curp = row.solicitante_curp ? `<small class="d-block text-muted font-monospace">${row.solicitante_curp}</small>` : '';
                    let tel = row.solicitante_telefono ? `<small class="d-block text-muted"><i class="fa-solid fa-phone fa-xs me-1"></i>${row.solicitante_telefono}</small>` : '';
                    return curp || tel ? `${curp}${tel}` : '<span class="text-muted small">Sin datos</span>';
                }
            },
            { 
                "data": "tipo_peticion",
                "render": function(data) {
                    let label = tramitesMap[data] || data;
                    return `<span class="badge bg-light text-dark border" style="font-size: 0.75rem; white-space: normal; text-align: left;">${label}</span>`;
                }
            },
            { 
                "data": "detalle",
                "render": function(data) {
                    return `<span class="text-truncate d-inline-block" style="max-width: 230px;" title="${data}">${data}</span>`;
                }
            },
            {
                "data": "estatus",
                "render": function(data) {
                    let badgeClass = 'badge-pendiente';
                    let label = data;
                    if (data === 'ENTREGADO') { badgeClass = 'badge-vivo'; label = 'ENTREGADO'; }
                    else if (data === 'EN_PROCESO') { badgeClass = 'badge-finalizado'; label = 'EN PROCESO'; }
                    else if (data === 'CANCELADO') { badgeClass = 'badge-finado'; label = 'CANCELADO'; }
                    return `<span class="badge-status ${badgeClass}">${label}</span>`;
                }
            },
            { 
                "data": "creado_en",
                "render": function(data) {
                    return `<small class="text-muted">${data ? data.substring(0, 16) : ''}</small>`;
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    let html = `
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary btn-ticket" data-id="${row.id}" title="Imprimir Ticket">
                                <i class="fa-solid fa-print"></i>
                            </button>
                            <a href="edit.php?id=${row.id}" class="btn btn-outline-secondary" title="Editar Petición">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="../peticiones/create.php?solicitante=${encodeURIComponent(row.solicitante_nombre)}&curp=${encodeURIComponent(row.solicitante_curp || '')}&tipo=${encodeURIComponent(row.tipo_peticion)}&detalle=${encodeURIComponent(row.detalle)}&folio_origen=${encodeURIComponent(row.folio)}" class="btn btn-outline-info" title="Escalar a Ventanilla de Seguimiento (Expediente / Dictamen)">
                                <i class="fa-solid fa-folder-tree"></i>
                            </a>
                    `;
                    if (row.estatus === 'PENDIENTE' || row.estatus === 'EN_PROCESO') {
                        html += `
                            <button class="btn btn-outline-success btn-entregar" data-id="${row.id}" title="Marcar como Entregado">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        `;
                    }
                    html += `
                            <button class="btn btn-outline-danger btn-eliminar" data-id="${row.id}" data-folio="${row.folio}" title="Eliminar Petición">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    return html;
                }
            }
        ],
        "order": [[0, "desc"]]
    });

    // Imprimir Ticket
    $('#pvTable').on('click', '.btn-ticket', function() {
        window.open('ticket.php?id=' + $(this).data('id'), '_blank');
    });

    // Cambiar Estatus
    function cambiarEstatus(id, estatus) {
        $.ajax({
            url: 'estado.php',
            type: 'POST',
            data: { id: id, estatus: estatus, csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Listo', text: response.message, confirmButtonColor: 'var(--secondary-color)' });
                    pvTable.ajax.reload(null, false);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    }

    $('#pvTable').on('click', '.btn-entregar', function() {
        cambiarEstatus($(this).data('id'), 'ENTREGADO');
    });

    // Soft Delete
    $('#pvTable').on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        const folio = $(this).data('folio');
        Swal.fire({
            title: '¿Eliminar petición?',
            html: `¿Está seguro de eliminar la petición <strong class="text-danger">${folio}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--color-danger)',
            cancelButtonColor: '#64748B'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete.php',
                    type: 'POST',
                    data: { id: id, csrf_token: csrfToken },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Eliminado', text: response.message, confirmButtonColor: 'var(--secondary-color)' });
                            pvTable.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo procesar la solicitud.', confirmButtonColor: 'var(--primary-color)' });
                    }
                });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
