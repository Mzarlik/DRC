<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

use Core\Services\PeticionRapidaService;

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
    <title>Nueva Petición Rápida - ERP DRC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/vendor/fontawesome/css/all.min.css">
    <link href="../../assets/vendor/tom-select/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link href="../../assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
    <style>
        .is-valid-curp { border-color: #0F766E !important; background-color: rgba(15, 118, 110, 0.05); }
        .is-invalid-curp { border-color: #BE123C !important; background-color: rgba(190, 18, 60, 0.05); }
    </style>
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
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-bolt text-warning me-2"></i> Nueva Petición Rápida</h2>
                    <p class="text-muted small mb-0">Atención ágil en ventanilla y expedición inmediata</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fa-solid fa-table-list me-1"></i> Control de Peticiones
                    </a>
                    <a href="reporte_diario.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-file-invoice me-1"></i> Reporte Diario
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form id="formPv" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo \Core\Auth::generateCSRF(); ?>">
                        <input type="hidden" name="ciudadano_id" id="ciudadano_id" value="">

                        <!-- Buscador Opcional en Padrón de Ciudadanos -->
                        <div class="p-3 mb-4 rounded" style="background-color: var(--table-header-bg); border: 1px solid var(--border-color);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-muted">
                                    <i class="fa-solid fa-search me-1 text-primary"></i> ¿El ciudadano ya está registrado en el padrón? (Opcional)
                                </span>
                                <small class="text-muted">Busca por CURP o Nombre para autocompletar</small>
                            </div>
                            <select id="buscador_ciudadano" placeholder="Escriba CURP o nombre del ciudadano si ya existe..."></select>
                        </div>

                        <!-- Datos del Solicitante (Campos Libres) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="solicitante_nombre" class="form-label fw-bold">
                                    Nombre Completo del Solicitante <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase-input" id="solicitante_nombre" name="solicitante_nombre" 
                                       placeholder="EJ: JUAN PÉREZ LÓPEZ" maxlength="150" required>
                                <div class="form-text">Campo libre directo para atención inmediata en ventanilla.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="solicitante_curp" class="form-label fw-bold">CURP (Opcional)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-uppercase-input" id="solicitante_curp" name="solicitante_curp" maxlength="18"
                                           placeholder="18 caracteres (RENAPO)">
                                    <span class="input-group-text d-none" id="curpFeedbackIcon"><i class="fa-solid fa-check text-success"></i></span>
                                </div>
                                <div class="form-text" id="curpHelp">Dejar vacío si no cuenta con CURP.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="solicitante_telefono" class="form-label fw-bold">Teléfono de Contacto</label>
                                <input type="tel" class="form-control" id="solicitante_telefono" name="solicitante_telefono" maxlength="10"
                                       placeholder="10 dígitos (Ej: 8341234567)">
                                <div class="form-text">Opcional para avisos de entrega.</div>
                            </div>
                        </div>

                        <!-- Tipo de Petición y Detalle -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="tipo_peticion" class="form-label fw-bold">
                                    Tipo de Petición / Trámite <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="tipo_peticion" name="tipo_peticion" required>
                                    <option value="">Seleccione el tipo de trámite oficial...</option>
                                    <?php foreach ($opciones as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['clave']); ?>">
                                        <?php echo htmlspecialchars($op['valor']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="detalle" class="form-label fw-bold">
                                    Detalle / Referencia del Trámite <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase-input" id="detalle" name="detalle" maxlength="255"
                                       placeholder="EJ: ACTA DE NACIMIENTO AÑO 1990 LIBRO 2 ACTA 45" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="index.php" class="btn btn-secondary px-3">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 py-2" id="btnSubmit">
                                <i class="fa-solid fa-paper-plane me-1"></i> Generar Petición
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
    // Regex oficial de CURP mexicana (18 caracteres)
    const regexCurp = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/;

    // Forzar mayúsculas en campos de texto
    $(document).on('input', '.text-uppercase-input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // Validar solo números en teléfono
    $('#solicitante_telefono').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, '').slice(0, 10));
    });

    // Validación interactiva de CURP en tiempo real
    $('#solicitante_curp').on('input', function() {
        const curp = $(this).val().trim().toUpperCase();
        const $icon = $('#curpFeedbackIcon');
        const $help = $('#curpHelp');

        if (curp.length === 0) {
            $(this).removeClass('is-valid-curp is-invalid-curp');
            $icon.addClass('d-none');
            $help.text('Dejar vacío si no cuenta con CURP.').removeClass('text-danger text-success');
        } else if (curp.length === 18 && regexCurp.test(curp)) {
            $(this).removeClass('is-invalid-curp').addClass('is-valid-curp');
            $icon.removeClass('d-none').html('<i class="fa-solid fa-check text-success"></i>');
            $help.text('CURP válida según formato RENAPO.').removeClass('text-danger').addClass('text-success fw-bold');
        } else {
            $(this).removeClass('is-valid-curp').addClass('is-invalid-curp');
            $icon.removeClass('d-none').html('<i class="fa-solid fa-triangle-exclamation text-danger"></i>');
            $help.text(`Formato incompleto o no válido (${curp.length}/18 caracteres).`).removeClass('text-success').addClass('text-danger fw-bold');
        }
    });

    // Inicializar TomSelect para búsqueda opcional en el padrón
    new TomSelect("#buscador_ciudadano", {
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
        onChange: function(value) {
            if (value) {
                const item = this.options[value];
                $('#ciudadano_id').val(value);
                if (item && item.text) {
                    const partes = item.text.split(' - ');
                    $('#solicitante_nombre').val(partes[0].trim());
                    if (partes[1]) {
                        $('#solicitante_curp').val(partes[1].trim()).trigger('input');
                    }
                }
            } else {
                $('#ciudadano_id').val('');
            }
        },
        placeholder: 'Escriba CURP o nombre del ciudadano registrado...',
        allowEmptyOption: true
    });

    // Validación y envío del formulario
    $('#formPv').on('submit', function(e) {
        e.preventDefault();

        const nombre = $('#solicitante_nombre').val().trim();
        const curp = $('#solicitante_curp').val().trim().toUpperCase();
        const tel = $('#solicitante_telefono').val().trim();
        const tipo = $('#tipo_peticion').val();
        const detalle = $('#detalle').val().trim();

        // 1. Validar Nombre
        if (nombre.length < 3) {
            Swal.fire({ icon: 'warning', title: 'Nombre requerido', text: 'Escriba el nombre completo del solicitante (mínimo 3 letras).', confirmButtonColor: 'var(--secondary-color)' });
            $('#solicitante_nombre').focus();
            return;
        }

        // 2. Validar CURP si se proporcionó
        if (curp.length > 0 && (!regexCurp.test(curp) || curp.length !== 18)) {
            Swal.fire({
                icon: 'warning',
                title: 'CURP no válida',
                html: `La CURP ingresada <strong>${curp}</strong> no cumple con el estándar de 18 caracteres de RENAPO.<br><br><small class="text-muted">Corrija la CURP o bórrela si el ciudadano no cuenta con ella.</small>`,
                confirmButtonColor: 'var(--secondary-color)'
            });
            $('#solicitante_curp').focus();
            return;
        }

        // 3. Validar Teléfono si se proporcionó
        if (tel.length > 0 && tel.length !== 10) {
            Swal.fire({ icon: 'warning', title: 'Teléfono no válido', text: 'El teléfono debe contener exactamente 10 dígitos numéricos.', confirmButtonColor: 'var(--secondary-color)' });
            $('#solicitante_telefono').focus();
            return;
        }

        // 4. Validar Tipo de Trámite
        if (!tipo) {
            Swal.fire({ icon: 'warning', title: 'Seleccione trámite', text: 'Debe elegir un tipo de petición o trámite de la lista.', confirmButtonColor: 'var(--secondary-color)' });
            $('#tipo_peticion').focus();
            return;
        }

        // 5. Validar Detalle
        if (detalle.length < 4) {
            Swal.fire({ icon: 'warning', title: 'Detalle requerido', text: 'Especifique la referencia o detalle del trámite solicitado (mínimo 4 caracteres).', confirmButtonColor: 'var(--secondary-color)' });
            $('#detalle').focus();
            return;
        }

        // Deshabilitar botón durante envío
        const $btn = $('#btnSubmit');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Registrando...');

        $.ajax({
            url: 'save.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Generar Petición');
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Petición Registrada',
                        html: `Folio asignado: <strong class="text-primary fs-4">${response.folio}</strong>`,
                        confirmButtonText: '<i class="fa-solid fa-list me-1"></i> Ir al Listado',
                        confirmButtonColor: 'var(--secondary-color)',
                        showCancelButton: true,
                        cancelButtonText: '<i class="fa-solid fa-print me-1"></i> Imprimir Ticket',
                        cancelButtonColor: 'var(--primary-color)'
                    }).then((result) => {
                        if (result.isDismissed) {
                            window.open('ticket.php?id=' + response.id, '_blank');
                        }
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error al Guardar', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Generar Petición');
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
