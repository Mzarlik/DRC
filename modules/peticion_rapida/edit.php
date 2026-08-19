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
    header("Location: index.php?toast=error&msg=" . urlencode("Petición no encontrada o eliminada"));
    exit;
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
    <title>Editar Petición <?php echo htmlspecialchars($pet['folio']); ?> - ERP DRC</title>
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
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Editar Petición</h2>
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
                                <label for="solicitante_telefono" class="form-label fw-bold">Teléfono de Contacto</label>
                                <input type="tel" class="form-control" id="solicitante_telefono" name="solicitante_telefono" maxlength="10"
                                       value="<?php echo htmlspecialchars($pet['solicitante_telefono'] ?? ''); ?>" placeholder="10 dígitos">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="tipo_peticion" class="form-label fw-bold">
                                    Tipo de Petición / Trámite <span class="text-danger">*</span>
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
                                    Estatus del Trámite <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="estatus" name="estatus" required>
                                    <option value="PENDIENTE" <?php echo ($pet['estatus'] === 'PENDIENTE') ? 'selected' : ''; ?>>PENDIENTE (En ventanilla)</option>
                                    <option value="EN_PROCESO" <?php echo ($pet['estatus'] === 'EN_PROCESO') ? 'selected' : ''; ?>>EN PROCESO (Búsqueda / Trámite)</option>
                                    <option value="ENTREGADO" <?php echo ($pet['estatus'] === 'ENTREGADO') ? 'selected' : ''; ?>>ENTREGADO (Finalizado)</option>
                                    <option value="CANCELADO" <?php echo ($pet['estatus'] === 'CANCELADO') ? 'selected' : ''; ?>>CANCELADO</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="detalle" class="form-label fw-bold">
                                    Detalle / Referencia del Trámite <span class="text-danger">*</span>
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
                title: 'CURP no válida',
                html: `La CURP <strong>${curp}</strong> no cumple con el estándar de 18 caracteres de RENAPO.`,
                confirmButtonColor: 'var(--secondary-color)'
            });
            return;
        }

        if (tel.length > 0 && tel.length !== 10) {
            Swal.fire({ icon: 'warning', title: 'Teléfono no válido', text: 'El teléfono debe tener 10 dígitos.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (!tipo) {
            Swal.fire({ icon: 'warning', title: 'Seleccione trámite', text: 'Seleccione un tipo de trámite.', confirmButtonColor: 'var(--secondary-color)' });
            return;
        }

        if (detalle.length < 4) {
            Swal.fire({ icon: 'warning', title: 'Detalle requerido', text: 'Escriba el detalle de la petición.', confirmButtonColor: 'var(--secondary-color)' });
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
                Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
            }
        });
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
