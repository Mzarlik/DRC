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

// Parámetros opcionales de escalamiento desde Ventanilla Rápida
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-folder-plus text-primary me-2"></i> Apertura de Expediente</h2>
                    <p class="text-muted small mb-0">Ventanilla de Seguimiento — Casos especiales, dictámenes y trámites de largo plazo</p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver a Expedientes
                </a>
            </div>

            <?php if (!empty($escalado_folio)): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center">
                <i class="fa-solid fa-link fa-lg me-3 text-info"></i>
                <div>
                    <strong>Trámite escalado desde Ventanilla Rápida (Folio: <?php echo $escalado_folio; ?>)</strong>
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
                                <select id="ciudadano_id" name="ciudadano_id" required placeholder="Buscar por CURP o nombre en el padrón..."></select>
                                <div class="form-text">
                                    ¿No está en el padrón? <a href="../ciudadanos/create.php" target="_blank" class="fw-semibold">Registrar ciudadano en padrón</a>.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="tipo_peticion" class="form-label fw-bold">
                                    Materia / Tipo de Caso <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="tipo_peticion" name="tipo_peticion" required>
                                    <option value="">Seleccione la materia del caso...</option>
                                    <option value="CORRECCION_ACTA">CORRECCIÓN ADMINISTRATIVA DE ACTA</option>
                                    <option value="ACLARACION">ACLARACIÓN JURÍDICA / COTEJO DE LIBRO</option>
                                    <option value="DIGITALIZACION">DIGITALIZACIÓN / ALTA EN BASE NACIONAL (RENAPO/SIDEC)</option>
                                    <option value="REGISTRO_EXTEMPORANEO">SOLICITUD DE REGISTRO EXTEMPORÁNEO</option>
                                    <option value="IDENTIDAD_GENERO">RECONOCIMIENTO DE IDENTIDAD DE GÉNERO</option>
                                    <option value="OFICIO_DIR_GENERAL">TRÁMITE TURNADO A DIRECCIÓN GENERAL</option>
                                    <option value="OTRO">OTRO TRÁMITE ESPECIAL EN SEGUIMIENTO</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="descripcion" class="form-label fw-bold">
                                    Antecedentes, Motivo del Trámite y Dictamen Requerido <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control text-uppercase-input" id="descripcion" name="descripcion" rows="5" 
                                          placeholder="EJ: SE RECIBE SOLICITUD DE RECTIFICACIÓN POR ERROR EN EL APELLIDO PATERNO DEL REGISTRADO EN EL LIBRO 2 ACTA 45 DEL AÑO 1980. SE TURNA A REVISIÓN DE ARCHIVO..." required><?php echo !empty($escalado_detalle) ? htmlspecialchars("TRÁMITE ESCALADO DESDE VENTANILLA RÁPIDA [FOLIO {$escalado_folio}]: {$escalado_detalle}") : ''; ?></textarea>
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
            Swal.fire({ icon: 'warning', title: 'Descripción detallada', text: 'Escriba una descripción de antecedentes de al menos 10 caracteres.', confirmButtonColor: 'var(--secondary-color)' });
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
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
