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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventanilla de Seguimiento - ERP DRC</title>
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
            <li><a href="../peticion_rapida/index.php"><i class="fa-solid fa-bolt text-warning"></i> <span class="sidebar-text">Petición Rápida</span></a></li>
            <li><a href="../peticion_rapida/reporte_diario.php"><i class="fa-solid fa-file-invoice text-info"></i> <span class="sidebar-text">Reporte Diario</span></a></li>
            <li class="active"><a href="index.php"><i class="fa-solid fa-folder-open text-primary"></i> <span class="sidebar-text">Ventanilla de Seguimiento</span></a></li>
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
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-folder-open text-primary me-2"></i> Ventanilla de Seguimiento</h2>
                    <p class="text-muted small mb-0">Control de expedientes, dictámenes jurídicos, aclaraciones y trámites en tránsito</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="../peticion_rapida/index.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-bolt text-warning me-1"></i> Ventanilla Rápida
                    </a>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Expediente
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="segTable" class="table table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Ciudadano Titular</th>
                                    <th>CURP</th>
                                    <th>Materia / Caso</th>
                                    <th>Antecedentes / Dictamen</th>
                                    <th>Estatus</th>
                                    <th>Fecha Apertura</th>
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

    const segTable = $('#segTable').DataTable({
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
                "data": null,
                "render": function(data, type, row) {
                    return `<span class="fw-semibold">${row.nombre} ${row.apellido_paterno} ${row.apellido_materno || ''}</span>`;
                }
            },
            { 
                "data": "curp",
                "render": function(data) {
                    return data ? `<small class="font-monospace text-muted">${data}</small>` : '<span class="text-muted small">Sin CURP</span>';
                }
            },
            { 
                "data": "tipo_peticion",
                "render": function(data) {
                    let label = data;
                    if (data === 'CORRECCION_ACTA') label = 'CORRECCIÓN DE ACTA';
                    else if (data === 'ACLARACION') label = 'ACLARACIÓN JURÍDICA';
                    else if (data === 'DIGITALIZACION') label = 'DIGITALIZACIÓN (RENAPO)';
                    else if (data === 'REGISTRO_EXTEMPORANEO') label = 'EXTEMPORÁNEO';
                    else if (data === 'IDENTIDAD_GENERO') label = 'IDENTIDAD DE GÉNERO';
                    else if (data === 'OFICIO_DIR_GENERAL') label = 'DIR. GENERAL';
                    return `<span class="badge bg-light text-dark border">${label}</span>`;
                }
            },
            { 
                "data": "descripcion",
                "render": function(data) {
                    return `<span class="text-truncate d-inline-block" style="max-width: 250px;" title="${data}">${data}</span>`;
                }
            },
            {
                "data": "estatus",
                "render": function(data) {
                    let badgeClass = 'badge-pendiente';
                    let label = data;
                    if (data === 'ABIERTA') { badgeClass = 'badge-pendiente'; label = 'ABIERTO'; }
                    else if (data === 'EN_PROGRESO') { badgeClass = 'badge-finalizado'; label = 'EN ANÁLISIS / PROCESO'; }
                    else if (data === 'CERRADA') { badgeClass = 'badge-vivo'; label = 'CONCLUIDO'; }
                    return `<span class="badge-status ${badgeClass}">${label}</span>`;
                }
            },
            { 
                "data": "fecha_creacion",
                "render": function(data) {
                    return `<small class="text-muted">${data ? data.substring(0, 10) : ''}</small>`;
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    let html = `<div class="btn-group btn-group-sm" role="group">`;
                    if (row.estatus === 'ABIERTA') {
                        html += `
                            <button class="btn btn-outline-primary btn-cambiar-estatus" data-id="${row.id}" data-estatus="EN_PROGRESO" title="Pasar a En Análisis">
                                <i class="fa-solid fa-gears me-1"></i> Analizar
                            </button>
                        `;
                    }
                    if (row.estatus !== 'CERRADA') {
                        html += `
                            <button class="btn btn-outline-success btn-cambiar-estatus" data-id="${row.id}" data-estatus="CERRADA" title="Concluir Expediente">
                                <i class="fa-solid fa-check me-1"></i> Concluir
                            </button>
                        `;
                    } else {
                        html += `<span class="badge bg-success-subtle text-success py-2 px-3"><i class="fa-solid fa-check-double me-1"></i> Finalizado</span>`;
                    }
                    html += `</div>`;
                    return html;
                }
            }
        ],
        "order": [[0, "desc"]]
    });

    // Manejo de cambio de estatus de expediente
    $('#segTable').on('click', '.btn-cambiar-estatus', function() {
        const id = $(this).data('id');
        const estatus = $(this).data('estatus');
        const label = estatus === 'CERRADA' ? 'concluir' : 'pasar a análisis';

        Swal.fire({
            title: `¿Desea ${label} este expediente?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: 'var(--secondary-color)'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    url: 'update_status.php',
                    type: 'POST',
                    data: { id: id, estatus: estatus, csrf_token: csrfToken },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Actualizado', text: resp.message, confirmButtonColor: 'var(--secondary-color)' });
                            segTable.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: resp.message, confirmButtonColor: 'var(--primary-color)' });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo actualizar el expediente.', confirmButtonColor: 'var(--primary-color)' });
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
