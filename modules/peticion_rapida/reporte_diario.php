<?php
require_once __DIR__ . '/../../core/Auth.php';
\Core\Auth::checkPermission('permiso_peticiones_rapidas');
\Core\Auth::check();

use Core\Services\PeticionRapidaService;

$fecha = $_GET['fecha'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

$reporte = PeticionRapidaService::getReporteDiario($fecha);

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
    <title>Reporte Diario Oficial - Registro Civil</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
    <style>
        .report-table th, .report-table td {
            vertical-align: middle;
            padding: 10px 14px;
        }
        .report-count-box {
            font-size: 1.1rem;
            font-weight: 700;
            min-width: 60px;
            text-align: right;
        }
        .count-active {
            color: var(--primary-color, #691C32);
            background-color: rgba(105, 28, 50, 0.08);
            border-radius: 6px;
            padding: 2px 10px;
            display: inline-block;
        }
        .count-zero {
            color: #94A3B8;
        }
        @media print {
            body { background: #fff !important; color: #000 !important; font-size: 12px; }
            #sidebar, .navbar, .no-print { display: none !important; }
            #content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .card { border: 1px solid #000 !important; box-shadow: none !important; }
            .report-header-print { display: block !important; text-align: center; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 15px; }
            .report-header-print h3 { font-size: 18px; font-weight: bold; margin: 0; text-transform: uppercase; }
            .report-header-print h4 { font-size: 14px; margin: 4px 0; }
            .report-table th { background: #f0f0f0 !important; color: #000 !important; border: 1px solid #000 !important; }
            .report-table td { border: 1px solid #ccc !important; }
            .badge { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; }
            .count-active { background: transparent !important; color: #000 !important; font-weight: bold; }
        }
        .report-header-print { display: none; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="offcanvas-lg offcanvas-start no-print" tabindex="-1">
        <div class="sidebar-header d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-building-columns"></i> <span class="sidebar-text">ERP DRC</span></span>
            <button type="button" class="btn-close btn-close-white d-md-none" id="sidebarCloseMobile" aria-label="Close"></button>
        </div>
        <ul class="list-unstyled components">
            <li><a href="<?php echo $db_link; ?>"><i class="fa-solid fa-chart-line"></i> <span class="sidebar-text">Dashboard</span></a></li>
            <li><a href="index.php"><i class="fa-solid fa-bolt"></i> <span class="sidebar-text">Petición Rápida</span></a></li>
            <li class="active"><a href="reporte_diario.php"><i class="fa-solid fa-file-invoice"></i> <span class="sidebar-text">Reporte Diario</span></a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg no-print">
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
            
            <!-- Cabecera Impresión Oficial -->
            <div class="report-header-print">
                <h3>DIRECCIÓN DE REGISTRO CIVIL</h3>
                <h4>REPORTE DIARIO DE ACTIVIDADES Y TRÁMITES DE VENTANILLA</h4>
                <p class="mb-0">Fecha de Corte: <strong><?php echo $reporte['fecha_fmt']; ?></strong> | Generado por: <strong><?php echo htmlspecialchars(\Core\Auth::getUserName()); ?></strong></p>
            </div>

            <!-- Cabecera Web -->
            <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Reporte Diario Oficial</h2>
                    <p class="text-muted small mb-0">Cédula de corte y balance diario de actividades de la Dirección de Registro Civil</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Ventanilla
                    </a>
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Imprimir Reporte
                    </button>
                    <button class="btn btn-success" id="btnExportarCsv">
                        <i class="fa-solid fa-file-csv me-1"></i> Exportar CSV
                    </button>
                </div>
            </div>

            <!-- Filtros de Fecha -->
            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-body p-3">
                    <form method="GET" action="reporte_diario.php" class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label for="fecha" class="col-form-label fw-bold small text-muted">
                                <i class="fa-regular fa-calendar text-primary me-1"></i> Fecha del Reporte:
                            </label>
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-filter me-1"></i> Consultar Fecha
                            </button>
                        </div>
                        <div class="col-auto ms-auto">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="toggleSoloConRegistros">
                                <label class="form-check-label small fw-semibold" for="toggleSoloConRegistros">
                                    Ocultar actividades en 0
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tarjetas de Resumen KPI -->
            <div class="row g-3 mb-4 no-print">
                <div class="col-md-4">
                    <div class="card-kpi kpi-burgundy">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Total Trámites del Día</div>
                                <div class="kpi-value"><?php echo number_format($reporte['gran_total']); ?></div>
                            </div>
                            <div class="kpi-icon-badge"><i class="fa-solid fa-list-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-kpi kpi-slate">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Fecha de Balance</div>
                                <div class="kpi-value fs-4"><?php echo $reporte['fecha_fmt']; ?></div>
                            </div>
                            <div class="kpi-icon-badge"><i class="fa-regular fa-calendar-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-kpi kpi-teal">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Catálogo Actividades</div>
                                <div class="kpi-value"><?php echo count($reporte['filas']); ?></div>
                            </div>
                            <div class="kpi-icon-badge"><i class="fa-solid fa-clipboard-list"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla del Reporte Diario Oficial -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover report-table mb-0" id="tablaReporte">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th>Concepto / Trámite Oficial (Dirección de Registro Civil)</th>
                                    <th style="width: 100px;" class="text-center">Código</th>
                                    <th style="width: 160px;" class="text-end">Total del Día</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($reporte['filas'] as $f): ?>
                                <tr class="fila-reporte <?php echo ($f['total'] == 0) ? 'fila-cero' : 'fila-activa'; ?>">
                                    <td class="text-center text-muted small"><?php echo $i++; ?></td>
                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($f['nombre']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border font-monospace"><?php echo htmlspecialchars($f['codigo']); ?></span>
                                    </td>
                                    <td class="report-count-box">
                                        <?php if ($f['total'] > 0): ?>
                                            <span class="count-active"><?php echo number_format($f['total']); ?></span>
                                        <?php else: ?>
                                            <span class="count-zero">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="3" class="text-end fs-6 text-uppercase">Total General de Actividades del Día:</th>
                                    <th class="text-end fs-5 fw-bold" style="color: #F8FAFC;"><?php echo number_format($reporte['gran_total']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Filtro interactivo para ocultar filas en 0
    $('#toggleSoloConRegistros').on('change', function() {
        if ($(this).is(':checked')) {
            $('.fila-cero').hide();
        } else {
            $('.fila-cero').show();
        }
    });

    // Exportar a CSV
    $('#btnExportarCsv').on('click', function() {
        let csv = '\uFEFF'; // BOM para acentos en Excel
        csv += 'REPORTE DIARIO - DIRECCIÓN DE REGISTRO CIVIL\n';
        csv += 'Fecha: <?php echo $reporte['fecha_fmt']; ?>\n\n';
        csv += '#,Concepto / Trámite,Código,Total del Día\n';

        $('#tablaReporte tbody tr').each(function() {
            let row = [];
            $(this).find('td').each(function(idx) {
                let text = $(this).text().trim().replace(/"/g, '""');
                row.push('"' + text + '"');
            });
            csv += row.join(',') + '\n';
        });

        csv += '\n,"TOTAL GENERAL",,"<?php echo $reporte['gran_total']; ?>"\n';

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'Reporte_Diario_DRC_<?php echo $reporte['fecha']; ?>.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
});
</script>
<script src="../../assets/js/global.js"></script>
</body>
</html>
