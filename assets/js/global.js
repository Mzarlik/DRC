/**
 * ERP DRC - Global JavaScript Controller
 * Controls Dark Mode, Keyboard Navigation, and Sidebar behaviors.
 */

// Apply theme immediately as early as possible to minimize FOUC
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    // Ensure body gets it too once DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('dark-mode');
        });
    } else {
        document.body.classList.add('dark-mode');
    }
}

// Overwrite DataTables defaults to globally enable Responsive extension & 100% Offline Spanish
if (window.jQuery && $.fn.dataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
        responsive: true,
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles en la tabla",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros en total)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        }
    });
}

// Global SweetAlert2 Toast Helper
window.showToast = function(type, title, text) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({
            icon: type || 'success',
            title: title || 'Operación realizada',
            text: text || ''
        });
    }
};

$(document).ready(function() {
    // URL parameter Toast checker
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('toast')) {
        const toastType = urlParams.get('toast');
        const toastMsg = urlParams.get('msg') || 'Operación realizada con éxito';
        
        setTimeout(() => {
            window.showToast(toastType, toastMsg);
        }, 300);
        
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
    // 1. DYNAMIC DARK MODE TOGGLE INJECTION
    const $navbarRight = $('.navbar .ms-auto');
    if ($navbarRight.length && !$('#darkModeToggle, #themeToggleBtn').length) {
        const isDark = localStorage.getItem('theme') === 'dark' || document.documentElement.classList.contains('dark-mode');
        const iconClass = isDark ? 'fa-sun' : 'fa-moon';
        const toggleHtml = `
            <button type="button" id="darkModeToggle" class="btn btn-link text-dark nav-link me-3 p-0 no-caret d-flex align-items-center justify-content-center" title="Alternar Modo Oscuro" style="border: none; background: none; font-size: 1.2rem; width: 36px; height: 36px; text-decoration: none;">
                <i class="fa-solid ${iconClass}"></i>
            </button>
        `;
        $navbarRight.prepend(toggleHtml);
        
        if (isDark) {
            $('#darkModeToggle').addClass('text-light').removeClass('text-dark');
        }
    }

    // Toggle click handler
    $(document).on('click', '#darkModeToggle, #themeToggleBtn', function(e) {
        e.preventDefault();
        const $body = $('body');
        const $icon = $(this).find('i');
        const isDark = $body.hasClass('dark-mode') || document.documentElement.classList.contains('dark-mode');

        if (isDark) {
            $body.removeClass('dark-mode');
            document.documentElement.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            $icon.removeClass('fa-sun').addClass('fa-moon');
            $(this).removeClass('text-light').addClass('text-dark');
        } else {
            $body.addClass('dark-mode');
            document.documentElement.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            $icon.removeClass('fa-moon').addClass('fa-sun');
            $(this).removeClass('text-dark').addClass('text-light');
        }
    });

    // =========================================================================
    // 2. CENTRO GLOBAL DE NOTIFICACIONES
    // =========================================================================
    function initGlobalNotifications() {
        const $notifMenu = $('#notificacionesMenu');
        if (!$notifMenu.length) return;

        // Determinar endpoint
        let endpoint = '/DRC/public/api/notifications.php';
        const pathname = window.location.pathname;
        if (pathname.includes('/modules/')) {
            endpoint = '../../public/api/notifications.php';
        } else if (pathname.includes('/public/')) {
            endpoint = 'api/notifications.php';
        }

        function refreshNotifications() {
            $.ajax({
                url: endpoint,
                type: 'GET',
                dataType: 'json',
                success: function(resp) {
                    if (!resp || resp.status !== 'success' || !Array.isArray(resp.notifications)) return;

                    const notifs = resp.notifications;
                    const $badge = $('#notifBadge');
                    const $total = $('#notifTotal');
                    const $empty = $('#notifEmpty');
                    const $list = $('#notifList');

                    $list.find('li.notif-item').remove();

                    if (notifs.length > 0) {
                        $badge.text(notifs.length > 99 ? '99+' : notifs.length).show();
                        $total.text(notifs.length);
                        $empty.hide();

                        notifs.forEach(function(item) {
                            const url = item.url || '#';
                            const urgentBorder = item.is_urgent ? 'notif-unread' : '';
                            const dlAttr = item.is_download ? 'download' : '';
                            
                            const html = `
                                <li class="notif-item border-bottom ${urgentBorder}">
                                    <a class="dropdown-item p-3 d-flex align-items-start" href="${url}" ${dlAttr} style="white-space: normal;">
                                        <div class="me-3 mt-1 flex-shrink-0">
                                            <i class="fa-solid ${item.icon || 'fa-info-circle'} ${item.color || 'text-primary'} fa-lg"></i>
                                        </div>
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                                <h6 class="mb-0 fw-bold small text-dark text-truncate">${item.title}</h6>
                                                <small class="text-muted ms-2 text-nowrap" style="font-size: 0.68rem;">${item.time}</small>
                                            </div>
                                            <p class="mb-0 text-muted small text-break" style="font-size: 0.8rem; line-height: 1.35;">${item.desc}</p>
                                        </div>
                                    </a>
                                </li>
                            `;
                            $list.append(html);
                        });
                    } else {
                        $badge.hide();
                        $total.text('0');
                        $empty.show();
                    }
                },
                error: function() {
                    // Silencioso en caso de error
                }
            });
        }

        refreshNotifications();
        window.refreshNotifications = refreshNotifications;
        setInterval(refreshNotifications, 30000);
    }

    initGlobalNotifications();

    /**
     * Motor global para exportación asíncrona a Excel con descarga automática instantánea.
     * Muestra loader, solicita generación, sondea estado del job y descarga el archivo automáticamente.
     */
    window.exportToExcelAsync = function(exportUrl, postData, customTitle) {
        if (typeof Swal === 'undefined') {
            alert('Generando archivo...');
            return;
        }

        Swal.fire({
            title: customTitle || 'Generando Reporte Excel',
            html: '<div class="my-3"><div class="spinner-border text-success" role="status"></div></div><p class="text-muted small mb-0">Procesando y compilando archivo Excel en el servidor...</p>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                $.ajax({
                    url: exportUrl,
                    type: 'POST',
                    data: postData,
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.status === 'success' && resp.job_id) {
                            pollExportJob(resp.job_id, 0);
                        } else if (resp && resp.status === 'success') {
                            if (typeof window.refreshNotifications === 'function') {
                                window.refreshNotifications();
                            }
                            Swal.fire({
                                icon: 'info',
                                title: 'Reporte en Proceso',
                                text: resp.message || 'El reporte se está procesando. Revisa la campana de notificaciones.',
                                confirmButtonColor: 'var(--secondary-color)'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (resp && resp.message) ? resp.message : 'No se pudo generar la exportación.',
                                confirmButtonColor: 'var(--primary-color)'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Red',
                            text: 'No se pudo conectar con el servidor para iniciar la exportación.',
                            confirmButtonColor: 'var(--primary-color)'
                        });
                    }
                });
            }
        });

        function pollExportJob(jobId, attempts) {
            if (attempts >= 20) {
                if (typeof window.refreshNotifications === 'function') {
                    window.refreshNotifications();
                }
                Swal.fire({
                    icon: 'info',
                    title: 'Generación en Progreso',
                    text: 'El archivo sigue generándose en segundo plano. Podrás descargarlo desde la campana de notificaciones en la barra superior en unos instantes.',
                    confirmButtonColor: 'var(--secondary-color)'
                });
                return;
            }

            let statusEndpoint = '/DRC/public/api/export_status.php';
            const pathname = window.location.pathname;
            if (pathname.includes('/modules/')) {
                statusEndpoint = '../../public/api/export_status.php';
            } else if (pathname.includes('/public/')) {
                statusEndpoint = 'api/export_status.php';
            }

            setTimeout(function() {
                $.ajax({
                    url: statusEndpoint,
                    type: 'GET',
                    data: { job_id: jobId },
                    dataType: 'json',
                    success: function(statusResp) {
                        if (statusResp && statusResp.status === 'completed' && statusResp.download_url) {
                            if (typeof window.refreshNotifications === 'function') {
                                window.refreshNotifications();
                            }

                            // Iniciar descarga automáticamente creando elemento ancla
                            const link = document.createElement('a');
                            link.href = statusResp.download_url;
                            link.setAttribute('download', statusResp.file_name || 'reporte.xlsx');
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);

                            Swal.fire({
                                icon: 'success',
                                title: '¡Descarga Lista!',
                                html: `<p class="mb-2">Tu reporte <strong>${statusResp.file_name || 'Excel'}</strong> se ha generado exitosamente y se está descargando.</p><a href="${statusResp.download_url}" class="btn btn-sm btn-success mt-2" download><i class="fa-solid fa-download me-1"></i> Volver a descargar</a>`,
                                confirmButtonColor: 'var(--secondary-color)',
                                confirmButtonText: 'Aceptar'
                            });
                        } else if (statusResp && statusResp.status === 'error') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al Generar',
                                text: statusResp.message || 'Ocurrió un error al procesar el archivo.',
                                confirmButtonColor: 'var(--primary-color)'
                            });
                        } else {
                            pollExportJob(jobId, attempts + 1);
                        }
                    },
                    error: function() {
                        pollExportJob(jobId, attempts + 1);
                    }
                });
            }, 600);
        }
    };

    // 2. KEYBOARD NAVIGATION
    // Move between inputs with Enter
    $(document).on('keydown', 'input, select, textarea', function(e) {
        if (e.key === 'Enter') {
            const $el = $(this);
            
            // Skip textareas (so user can type line breaks)
            if ($el.is('textarea')) {
                return;
            }
            
            // Skip button click events or submit buttons
            if ($el.is('button') || $el.attr('type') === 'submit' || $el.attr('type') === 'button') {
                return;
            }
            
            e.preventDefault();
            
            const $form = $el.closest('form');
            if ($form.length) {
                // Compile list of focusable fields including TomSelect input boxes
                let focusableElements = [];
                $form.find('input, textarea, select').each(function() {
                    const $item = $(this);
                    
                    // Skip hidden fields (that aren't part of TomSelect wrapper)
                    if ($item.is(':hidden') && !$item.hasClass('tomselected')) {
                        return;
                    }
                    
                    // Skip disabled or readonly
                    if ($item.attr('disabled') || $item.attr('readonly') || $item.attr('type') === 'hidden') {
                        return;
                    }
                    
                    // Skip buttons
                    if ($item.attr('type') === 'submit' || $item.attr('type') === 'button' || $item.is('button')) {
                        return;
                    }
                    
                    // If it is a TomSelect select (hidden), focus its corresponding search input instead
                    if ($item.hasClass('tomselected')) {
                        const tsInput = $item.next('.ts-wrapper').find('.ts-control input');
                        if (tsInput.length && tsInput.is(':visible')) {
                            focusableElements.push(tsInput[0]);
                        }
                    } else {
                        focusableElements.push(this);
                    }
                });
                
                const $focusableList = $(focusableElements);
                const currentIndex = $focusableList.index(this);
                
                if (currentIndex > -1 && currentIndex + 1 < $focusableList.length) {
                    const $nextField = $focusableList.eq(currentIndex + 1);
                    $nextField.focus();
                    
                    // If it's a TomSelect input element, open the dropdown too
                    if ($nextField.closest('.ts-wrapper').length) {
                        $nextField.click();
                    }
                }
            }
        }
    });

    // Save/Submit Form with Ctrl+Enter
    $(document).on('keydown', 'input, select, textarea', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            const $form = $(this).closest('form');
            if ($form.length) {
                // Try to find submit button first to click it (for validation / AJAX handlers)
                const $submitBtn = $form.find('[type="submit"], button:not([type="button"])').first();
                if ($submitBtn.length) {
                    $submitBtn.click();
                } else {
                    $form.submit();
                }
            }
        }
    });

    // 3. SIDEBAR CENTRALIZED LÓGICA (Desktop Compact & Mobile Offcanvas)
    function initSidebarLogic() {
        const $sidebar = $('#sidebar');
        if (!$sidebar.length) return;

        // Tooltip management for compact sidebar
        let sidebarTooltips = [];

        function updateSidebarTooltips() {
            // Destroy existing tooltips first
            sidebarTooltips.forEach(t => {
                try { t.dispose(); } catch (e) {}
            });
            sidebarTooltips = [];

            if ($sidebar.hasClass('compact') && $(window).width() >= 992 && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                $sidebar.find('ul.components > li > a').each(function() {
                    const text = $(this).find('.sidebar-text').text().trim() || $(this).text().trim();
                    if (text) {
                        const tip = new bootstrap.Tooltip(this, {
                            title: text,
                            placement: 'right',
                            trigger: 'hover',
                            container: 'body'
                        });
                        sidebarTooltips.push(tip);
                    }
                });
            }
        }

        function setSidebarCompactState(isCompact) {
            if (isCompact) {
                $sidebar.addClass('compact');
                localStorage.setItem('sidebar_compact', 'true');
            } else {
                $sidebar.removeClass('compact');
                localStorage.setItem('sidebar_compact', 'false');
            }
            updateSidebarTooltips();
            setTimeout(() => {
                if ($.fn.dataTable) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }
            }, 300);
        }

        // Restore saved preference on desktop
        if ($(window).width() >= 992) {
            if (localStorage.getItem('sidebar_compact') === 'true') {
                $sidebar.addClass('compact');
            }
            updateSidebarTooltips();
        }

        // Toggle button click (Desktop compact / Mobile offcanvas)
        $(document).on('click', '#sidebarCollapse', function (e) {
            e.preventDefault();
            if ($(window).width() >= 992) {
                const nowCompact = !$sidebar.hasClass('compact');
                setSidebarCompactState(nowCompact);
            } else {
                const sidebarEl = document.getElementById('sidebar');
                if (sidebarEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarEl);
                    bsOffcanvas.toggle();
                }
            }
        });

        // Mobile close button
        $(document).on('click', '#sidebarCloseMobile', function (e) {
            e.preventDefault();
            if ($(window).width() < 992) {
                const sidebarEl = document.getElementById('sidebar');
                if (sidebarEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebarEl);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            }
        });

        // Expand sidebar if it's compact and user clicks a submenu toggle
        $sidebar.on('click', '.dropdown-toggle', function () {
            if ($sidebar.hasClass('compact') && $(window).width() >= 992) {
                setSidebarCompactState(false);
            }
        });

        // Close mobile drawer when clicking a navigation link (not dropdown header)
        $sidebar.on('click', 'a:not(.dropdown-toggle)', function() {
            if ($(window).width() < 992) {
                const sidebarEl = document.getElementById('sidebar');
                if (sidebarEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebarEl);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            }
        });

        // Touch swipe-left to close mobile offcanvas drawer
        let touchStartX = 0;
        let touchStartY = 0;
        const sidebarDom = document.getElementById('sidebar');
        if (sidebarDom) {
            sidebarDom.addEventListener('touchstart', function(e) {
                if ($(window).width() < 992 && e.touches.length === 1) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                }
            }, { passive: true });

            sidebarDom.addEventListener('touchend', function(e) {
                if ($(window).width() < 992 && e.changedTouches.length === 1) {
                    const touchEndX = e.changedTouches[0].clientX;
                    const touchEndY = e.changedTouches[0].clientY;
                    const diffX = touchStartX - touchEndX;
                    const diffY = Math.abs(touchStartY - touchEndY);
                    // Swipe left detected (at least 45px horizontal swipe with low vertical movement)
                    if (diffX > 45 && diffY < 60) {
                        const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebarDom);
                        if (bsOffcanvas) {
                            bsOffcanvas.hide();
                        }
                    }
                }
            }, { passive: true });
        }

        // Clean up active offcanvas states if window is resized past 992px
        $(window).on('resize', function() {
            if ($(window).width() >= 992) {
                const sidebarEl = document.getElementById('sidebar');
                if (sidebarEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebarEl);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
                if (localStorage.getItem('sidebar_compact') === 'true') {
                    $sidebar.addClass('compact');
                } else {
                    $sidebar.removeClass('compact');
                }
                updateSidebarTooltips();
            } else {
                $sidebar.removeClass('compact');
                updateSidebarTooltips();
            }
        });

        // Retirar clase de carga para evitar FOUC
        document.documentElement.classList.remove('sidebar-compact-loading');
    }

    initSidebarLogic();

    // 4. UTILITIES
    // Automatic conversion of uppercase inputs
    $(document).on('input', '.text-uppercase-input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // 5. RESPONSIVE TABLES & MOBILE CARDS TOGGLER
    if (window.jQuery && $.fn.dataTable) {
        // Initialize view switcher and label injector for each DataTable on the page
        $.fn.dataTable.tables({ api: true }).each(function() {
            setupTableResponsiveness(this);
        });

        // Also listen for any dynamically initialized tables
        $(document).on('init.dt', function(e, settings) {
            const api = new $.fn.dataTable.Api(settings);
            setupTableResponsiveness(api);
        });

        // Window resize handler for view state persistence
        $(window).on('resize', function() {
            if ($(window).width() < 768) {
                $.fn.dataTable.tables({ api: true }).each(function() {
                    const $wrapper = $(this.table().container());
                    const $cardsContainer = $wrapper.next('.table-mobile-cards');
                    const isCardsActive = $wrapper.find('.btn-view-cards').hasClass('active');
                    
                    if (isCardsActive) {
                        $wrapper.find('.dataTables_scroll, table.dataTable, .dataTables_info, .dataTables_paginate').addClass('d-none');
                        $cardsContainer.removeClass('d-none');
                    } else {
                        $wrapper.find('.dataTables_scroll, table.dataTable, .dataTables_info, .dataTables_paginate').removeClass('d-none');
                        $cardsContainer.addClass('d-none');
                    }
                });
            } else {
                $.fn.dataTable.tables({ api: true }).each(function() {
                    const $wrapper = $(this.table().container());
                    const $cardsContainer = $wrapper.next('.table-mobile-cards');
                    $wrapper.find('.dataTables_scroll, table.dataTable, .dataTables_info, .dataTables_paginate').removeClass('d-none');
                    $cardsContainer.addClass('d-none');
                });
            }
        });
        // Tab change listener to recalculate columns and rebuild mobile cards for visible tables
        $(document).on('shown.bs.tab', 'button[data-bs-toggle="tab"], button[data-bs-toggle="pill"], a[data-bs-toggle="tab"], a[data-bs-toggle="pill"]', function() {
            setTimeout(() => {
                $.fn.dataTable.tables({ visible: true, api: true }).each(function() {
                    this.columns.adjust();
                    if (this.responsive) {
                        this.responsive.recalc();
                    }
                    if ($(window).width() < 768) {
                        const $wrapper = $(this.table().container());
                        const $cardsContainer = $wrapper.next('.table-mobile-cards');
                        if ($cardsContainer.length && !$cardsContainer.hasClass('d-none')) {
                            this.draw(false);
                        }
                    }
                });
            }, 100);
        });
    }

    // Helper function to setup responsiveness switcher and cards
    function setupTableResponsiveness(api) {
        const $table = $(api.table().node());
        const $wrapper = $(api.table().container());

        // Prevent duplicate initialization
        if ($table.data('responsive-initialized')) return;
        $table.data('responsive-initialized', true);

        // Create sibling container for cards list on mobile
        const $cardsContainer = $('<div class="table-mobile-cards d-none"></div>');
        $wrapper.after($cardsContainer);

        // Function to rebuild cards list from current DataTable data
        function rebuildMobileCards() {
            $cardsContainer.empty();
            const headers = [];
            
            // Get headers text
            api.columns().every(function() {
                headers.push($(this.header()).text().trim());
            });

            // Get rows
            api.rows({ page: 'current' }).every(function() {
                const $row = $(this.node());
                if (!$row.length) return;
                
                let cardTitle = '';
                let cardBadge = '';
                let cardDetails = [];
                let cardActions = '';

                $row.find('td').each(function(colIdx) {
                    const $cell = $(this);
                    const label = headers[colIdx] || '';
                    const valHtml = $cell.html();
                    const valText = $cell.text().trim();

                    // Skip control columns
                    if ($cell.hasClass('dtr-control')) return;

                    // Identify if it's the actions cell
                    if ($cell.find('.btn, a.btn').length || $cell.hasClass('actions-cell') || label.toLowerCase() === 'acciones') {
                        cardActions = valHtml;
                    } else if (
                        label.toLowerCase() === 'nombre' || 
                        label.toLowerCase() === 'nombre completo' || 
                        label.toLowerCase() === 'ciudadano' || 
                        label.toLowerCase() === 'usuario' || 
                        label.toLowerCase() === 'solicitante' ||
                        label.toLowerCase() === 'finado' ||
                        label.toLowerCase() === 'inscrito' ||
                        label.toLowerCase() === 'reconocido' ||
                        label.toLowerCase() === 'contrayente 1' ||
                        label.toLowerCase() === 'divorciado 1' ||
                        label.toLowerCase() === 'titular'
                    ) {
                        cardTitle = valHtml;
                    } else if (
                        label.toLowerCase() === 'id' || 
                        label.toLowerCase() === 'no. acta' || 
                        label.toLowerCase() === 'número de acta' || 
                        label.toLowerCase() === 'numero_acta' || 
                        label.toLowerCase() === 'acta' || 
                        label.toLowerCase() === 'clave' || 
                        label.toLowerCase() === 'folio ventanilla' || 
                        label.toLowerCase() === 'folio' ||
                        label.toLowerCase() === 'ticket' ||
                        label.toLowerCase() === 'turno'
                    ) {
                        cardBadge = valText;
                    } else {
                        // Limpiar nombres de etiqueta demasiado largos
                        let cleanLabel = label;
                        const lLow = label.toLowerCase();
                        if (lLow.includes('tipo de constancia') || lLow.includes('tipo de trámite') || lLow.includes('tipo de acto') || lLow.includes('trámite solicitado')) cleanLabel = 'Tipo';
                        else if (lLow.includes('línea de pago') || lLow.includes('linea de pago')) cleanLabel = 'Línea Pago';
                        else if (lLow.includes('fecha de nacimiento')) cleanLabel = 'Fecha Nac.';
                        else if (lLow.includes('fecha de defunción')) cleanLabel = 'Fecha Def.';
                        else if (lLow.includes('fecha de registro') || lLow.includes('fecha registro')) cleanLabel = 'Fecha Reg.';
                        else if (lLow.includes('lugar de nacimiento')) cleanLabel = 'Lugar Nac.';
                        else if (lLow.includes('lugar de origen') || lLow.includes('estado origen')) cleanLabel = 'Origen';
                        else if (lLow.includes('detalle / referencia')) cleanLabel = 'Detalle';
                        else if (lLow.includes('curp / contacto')) cleanLabel = 'Contacto';
                        
                        cardDetails.push({ label: cleanLabel, value: valHtml });
                    }
                });

                // Detección y combinación de parejas de contrayentes / divorciados
                const c1Idx = cardDetails.findIndex(d => d.label.toLowerCase().includes('primer contrayente') || d.label.toLowerCase().includes('contrayente 1'));
                const c2Idx = cardDetails.findIndex(d => d.label.toLowerCase().includes('segundo contrayente') || d.label.toLowerCase().includes('contrayente 2'));
                if (c1Idx !== -1 && c2Idx !== -1) {
                    cardTitle = `${cardDetails[c1Idx].value} <span class="text-muted small">&</span> ${cardDetails[c2Idx].value}`;
                    const firstIdx = Math.min(c1Idx, c2Idx);
                    const secondIdx = Math.max(c1Idx, c2Idx);
                    cardDetails.splice(secondIdx, 1);
                    cardDetails.splice(firstIdx, 1);
                }

                const div1Idx = cardDetails.findIndex(d => d.label.toLowerCase().includes('divorciado 1'));
                const div2Idx = cardDetails.findIndex(d => d.label.toLowerCase().includes('divorciado 2'));
                if (div1Idx !== -1 && div2Idx !== -1) {
                    cardTitle = `${cardDetails[div1Idx].value} <span class="text-muted small">y</span> ${cardDetails[div2Idx].value}`;
                    const firstIdx = Math.min(div1Idx, div2Idx);
                    const secondIdx = Math.max(div1Idx, div2Idx);
                    cardDetails.splice(secondIdx, 1);
                    cardDetails.splice(firstIdx, 1);
                }

                // If we didn't find a specific title, use the first detail
                if (!cardTitle && cardDetails.length > 0) {
                    const nameIdx = cardDetails.findIndex(d => 
                        d.label.toLowerCase().includes('nombre') || 
                        d.label.toLowerCase().includes('usuario') || 
                        d.label.toLowerCase().includes('ciudadano') ||
                        d.label.toLowerCase().includes('solicitante') ||
                        d.label.toLowerCase().includes('concepto') ||
                        d.label.toLowerCase().includes('descripción')
                    );
                    if (nameIdx !== -1) {
                        cardTitle = cardDetails[nameIdx].value;
                        cardDetails.splice(nameIdx, 1);
                    } else {
                        cardTitle = cardDetails[0].value;
                        cardDetails.shift();
                    }
                }

                // Emparejar Fecha Trámite y Fecha Llegada en una sola fila compacta
                const tramIdx = cardDetails.findIndex(d => d.label.toLowerCase().includes('trámite') || d.label.toLowerCase().includes('tramite'));
                const llegIdx = cardDetails.findIndex(d => d.label.toLowerCase().includes('llegada'));
                if (tramIdx !== -1 && llegIdx !== -1) {
                    const tramVal = cardDetails[tramIdx].value;
                    const llegVal = cardDetails[llegIdx].value;
                    const pairedRow = {
                        label: 'Fechas',
                        value: `<span class="text-muted small me-1">Trámite:</span>${tramVal} <span class="text-muted small ms-2 me-1">Llegada:</span>${llegVal}`
                    };
                    const firstIdx = Math.min(tramIdx, llegIdx);
                    const secondIdx = Math.max(tramIdx, llegIdx);
                    cardDetails.splice(secondIdx, 1);
                    cardDetails.splice(firstIdx, 1, pairedRow);
                }

                // Emparejar Sexo y Fecha Nac. (Ciudadanos)
                const sexoIdx = cardDetails.findIndex(d => d.label.toLowerCase() === 'sexo');
                const fnacIdx = cardDetails.findIndex(d => d.label.toLowerCase().includes('fecha nac'));
                if (sexoIdx !== -1 && fnacIdx !== -1) {
                    const sexoVal = cardDetails[sexoIdx].value;
                    const fnacVal = cardDetails[fnacIdx].value;
                    const pairedRow = {
                        label: 'Datos',
                        value: `<span class="badge bg-light text-dark border me-1">${sexoVal}</span><span class="small font-monospace">${fnacVal}</span>`
                    };
                    const firstIdx = Math.min(sexoIdx, fnacIdx);
                    const secondIdx = Math.max(sexoIdx, fnacIdx);
                    cardDetails.splice(secondIdx, 1);
                    cardDetails.splice(firstIdx, 1, pairedRow);
                }

                // Emparejar Estado Vital y Estatus (Ciudadanos)
                const vitalIdx = cardDetails.findIndex(d => d.label.toLowerCase().includes('estado vital'));
                const estatusIdx = cardDetails.findIndex(d => d.label.toLowerCase() === 'estatus' || d.label.toLowerCase() === 'estado');
                if (vitalIdx !== -1 && estatusIdx !== -1 && vitalIdx !== estatusIdx) {
                    const vitalVal = cardDetails[vitalIdx].value;
                    const estatusVal = cardDetails[estatusIdx].value;
                    const pairedRow = {
                        label: 'Estado',
                        value: `${vitalVal} ${estatusVal}`
                    };
                    const firstIdx = Math.min(vitalIdx, estatusIdx);
                    const secondIdx = Math.max(vitalIdx, estatusIdx);
                    cardDetails.splice(secondIdx, 1);
                    cardDetails.splice(firstIdx, 1, pairedRow);
                }

                // Detectar fila de estatus para acoplar acciones inline
                const statusIdx = cardDetails.findIndex(d => d.label.toLowerCase().includes('estatus') || d.label.toLowerCase().includes('estado'));

                // Build Card HTML
                let cardHtml = `
                    <div class="card mobile-record-card border-1">
                        <div class="card-header-mobile">
                            <div class="small-title">${cardTitle}</div>
                            ${cardBadge ? `<span class="badge bg-primary text-white">${cardBadge}</span>` : ''}
                        </div>
                        <div class="card-body">
                `;

                cardDetails.forEach(function(detail, idx) {
                    if (idx === statusIdx && cardActions) {
                        cardHtml += `
                            <div class="card-detail-row align-items-center">
                                <span class="card-detail-label">${detail.label}</span>
                                <div class="card-detail-value d-flex align-items-center justify-content-end gap-2 flex-wrap">
                                    <span>${detail.value}</span>
                                    <div class="card-inline-actions">${cardActions}</div>
                                </div>
                            </div>
                        `;
                        cardActions = ''; // Integrado
                    } else {
                        cardHtml += `
                            <div class="card-detail-row">
                                <span class="card-detail-label">${detail.label}</span>
                                <span class="card-detail-value">${detail.value}</span>
                            </div>
                        `;
                    }
                });

                if (cardActions) {
                    cardHtml += `
                        <div class="card-actions-row">
                            ${cardActions}
                        </div>
                    `;
                }

                cardHtml += `
                        </div>
                    </div>
                `;

                $cardsContainer.append(cardHtml);
            });
        }

        // Bind rebuild on draw event
        api.on('draw', function() {
            if (!$cardsContainer.hasClass('d-none')) {
                rebuildMobileCards();
            }
        });

        // Inject View Switcher control at the top of the DataTable wrapper
        const switcherId = 'switcher_' + ($table.attr('id') || Math.random().toString(36).substr(2, 9));
        const switcherHtml = `
            <div class="view-switcher-container d-flex justify-content-end mb-2 d-md-none">
                <div class="btn-group btn-group-sm" role="group" aria-label="Toggle View">
                    <button type="button" class="btn btn-primary btn-view-cards active" id="btn_cards_${switcherId}" style="background: var(--secondary-color); border: 1px solid var(--secondary-color);">
                        <i class="fa-solid fa-table-cells-large me-1"></i> Tarjetas
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-view-table" id="btn_table_${switcherId}" style="border: 1px solid var(--border-color);">
                        <i class="fa-solid fa-table me-1"></i> Tabla
                    </button>
                </div>
            </div>
        `;
        
        $wrapper.prepend(switcherHtml);

        const $btnCards = $wrapper.find(`#btn_cards_${switcherId}`);
        const $btnTable = $wrapper.find(`#btn_table_${switcherId}`);

        $btnCards.on('click', function() {
            $btnCards.addClass('active btn-primary').removeClass('btn-outline-secondary').css({
                'background': 'var(--secondary-color)',
                'border-color': 'var(--secondary-color)'
            });
            $btnTable.removeClass('active btn-primary').addClass('btn-outline-secondary').css({
                'background': '',
                'border-color': 'var(--border-color)'
            });

            // Hide table view, show cards view
            $wrapper.find('.dataTables_scroll, table.dataTable, .dataTables_info, .dataTables_paginate').addClass('d-none');
            $cardsContainer.removeClass('d-none');
            
            rebuildMobileCards();
        });

        $btnTable.on('click', function() {
            $btnTable.addClass('active btn-primary').removeClass('btn-outline-secondary').css({
                'background': 'var(--secondary-color)',
                'border-color': 'var(--secondary-color)'
            });
            $btnCards.removeClass('active btn-primary').addClass('btn-outline-secondary').css({
                'background': '',
                'border-color': 'var(--border-color)'
            });

            // Show table view, hide cards view
            $wrapper.find('.dataTables_scroll, table.dataTable, .dataTables_info, .dataTables_paginate').removeClass('d-none');
            $cardsContainer.addClass('d-none');
            
            // Trigger DataTable Responsive recalculation
            api.responsive.recalc();
        });

        // Default view: Cards on mobile upon initialization
        if ($(window).width() < 768) {
            setTimeout(() => {
                $btnCards.click();
            }, 150);
        }
    }

    // For plain HTML tables, inject data-label and wrap them
    $('table:not(.dataTable)').each(function() {
        const $table = $(this);
        if ($table.hasClass('no-card-responsive')) return;

        const headers = [];
        $table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });

        $table.find('tbody tr').each(function() {
            $(this).find('td').each(function(index) {
                if (!$(this).attr('data-label')) {
                    $(this).attr('data-label', headers[index] || '');
                }
                if ($(this).find('.btn, a.btn').length) {
                    $(this).addClass('actions-cell');
                }
            });
        });
        
        if (!$table.parent().hasClass('table-responsive-cards')) {
            $table.wrap('<div class="table-responsive-cards"></div>');
        }
    });

    // 6. REUBICACIÓN DE BOTONES DE ACCIÓN EN MÓVILES (BARRA FLOTANTE FIJA)
    function setupMobileActionButtons() {
        if ($(window).width() < 768) {
            const selectors = [
                '.container-fluid > .d-flex.justify-content-between:first-child div:has(.btn)',
                '.container-fluid > div:first-child.d-flex.justify-content-between div:has(.btn)',
                '.page-header-responsive div:has(.btn)',
                '.container-fluid > .d-flex.justify-content-end.mb-3:has(#btnExportAcciones, #btnExportErrores)'
            ];
            
            const $actionContainers = $(selectors.join(', '));

            if ($actionContainers.length && !$('.mobile-action-bar').length) {
                const $mobileBar = $('<div class="mobile-action-bar d-md-none"></div>');
                
                $actionContainers.each(function() {
                    const $div = $(this);
                    if (!$div.hasClass('mobile-action-bar-processed')) {
                        $div.addClass('mobile-action-bar-processed');
                        $div.children('.btn, a.btn').appendTo($mobileBar);
                        $div.addClass('d-none');
                    }
                });

                if ($mobileBar.children().length) {
                    $('body').append($mobileBar);
                    $('body').addClass('has-mobile-action-bar');
                }
            }
        } else {
            const $actionDivs = $('.mobile-action-bar-processed');
            const $mobileBar = $('.mobile-action-bar');
            if ($mobileBar.length) {
                $actionDivs.each(function() {
                    const $div = $(this);
                    $mobileBar.children().appendTo($div);
                    $div.removeClass('d-none mobile-action-bar-processed');
                });
                $mobileBar.remove();
                $('body').removeClass('has-mobile-action-bar');
            }
        }
    }

    // Run on load and window resize
    setupMobileActionButtons();
    $(window).on('resize', setupMobileActionButtons);
});

/**
 * Inicializa un selector TomSelect para búsqueda remota de ciudadanos (Vanilla JS).
 * @param {string|HTMLElement} elementId ID o elemento DOM
 * @param {Object} customConfig Opciones adicionales
 * @returns {TomSelect|null}
 */
function initCiudadanoSelect(elementId, customConfig = {}) {
    if (typeof TomSelect === 'undefined') return null;
    const el = (typeof elementId === 'string') ? document.getElementById(elementId) : elementId;
    if (!el) return null;

    // Destruir instancia previa si ya existe
    if (el.tomselect) {
        el.tomselect.destroy();
    }

    const defaultUrl = customConfig.searchUrl || (
        window.location.pathname.includes('/modules/') 
            ? '../ciudadanos/search.php' 
            : 'modules/ciudadanos/search.php'
    );

    return new TomSelect(el, Object.assign({
        valueField: 'id',
        labelField: 'nombre_completo',
        searchField: ['nombre_completo', 'curp'],
        maxItems: 1,
        placeholder: customConfig.placeholder || 'Escriba nombre o CURP...',
        loadThrottle: 300,
        create: false,
        plugins: ['clear_button'],
        load: function(query, callback) {
            if (query.length < 3) return callback();
            
            const sep = defaultUrl.includes('?') ? '&' : '?';
            fetch(`${defaultUrl}${sep}q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(json => {
                    callback(json.data || []);
                })
                .catch(() => callback());
        },
        render: {
            option: function(item, escape) {
                return `<div class="py-2 px-3 border-bottom">
                    <div class="fw-bold text-dark">${escape(item.nombre_completo)}</div>
                    <small class="text-muted"><i class="fa-solid fa-id-card me-1 text-primary"></i>CURP: ${escape(item.curp || 'S/C')}</small>
                </div>`;
            },
            item: function(item, escape) {
                return `<div><strong>${escape(item.nombre_completo)}</strong> <span class="text-muted">(${escape(item.curp || 'S/C')})</span></div>`;
            },
            no_results: function(data, escape) {
                return `<div class="p-2 text-muted small">No se encontraron ciudadanos con: "${escape(data.input)}"</div>`;
            }
        }
    }, customConfig));
}

// =========================================================================
// 6. MODAL UNIVERSAL DE REGISTRO RÁPIDO DE CIUDADANOS
// =========================================================================
function initQuickCitizenModal() {
    // 1. Inyectar HTML del Modal si no existe en la página
    if (!$('#modalQuickCiudadano').length) {
        const modalHtml = `
        <div class="modal fade" id="modalQuickCiudadano" tabindex="-1" aria-labelledby="modalQuickCiudadanoLabel" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title fw-bold" id="modalQuickCiudadanoLabel">
                            <i class="fa-solid fa-user-plus me-2"></i> Registrar Nuevo Ciudadano en Padrón
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formQuickCiudadano" autocomplete="off">
                        <input type="hidden" name="csrf_token" id="quick_csrf_token" value="">
                        <input type="hidden" id="quick_target_select" value="">
                        <div class="modal-body p-4">
                            <div class="alert alert-light border d-flex align-items-center mb-3 py-2 px-3">
                                <i class="fa-solid fa-circle-info text-primary fa-lg me-3"></i>
                                <div class="small">
                                    El ciudadano quedará registrado en el padrón oficial y será <strong>seleccionado automáticamente</strong> en el formulario actual sin recargar la página.
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Nombre(s) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase-input" id="quick_nombre" name="nombre" placeholder="EJ: JUAN CARLOS" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Primer Apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase-input" id="quick_apellido_paterno" name="apellido_paterno" placeholder="EJ: PÉREZ" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Segundo Apellido</label>
                                    <input type="text" class="form-control text-uppercase-input" id="quick_apellido_materno" name="apellido_materno" placeholder="EJ: LÓPEZ">
                                </div>
                            </div>

                            <div class="row g-3 mb-2">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold small">CURP (Opcional)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-uppercase-input" id="quick_curp" name="curp" maxlength="18" placeholder="18 CARACTERES">
                                        <span class="input-group-text d-none" id="quickCurpFeedback"><i class="fa-solid fa-check text-success"></i></span>
                                    </div>
                                    <div class="form-text" id="quickCurpHelp" style="font-size: 0.75rem;">Dejar vacío si es recién nacido o alta de CURP.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Sexo <span class="text-danger">*</span></label>
                                    <select class="form-select" id="quick_sexo" name="sexo" required>
                                        <option value="">Seleccione...</option>
                                        <option value="H">HOMBRE (H)</option>
                                        <option value="M">MUJER (M)</option>
                                        <option value="X">NO BINARIO / OTRO (X)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="quick_fecha_nacimiento" name="fecha_nacimiento" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success px-4" id="btnQuickGuardar" style="background: var(--secondary-color); border: none;">
                                <i class="fa-solid fa-save me-1"></i> Guardar y Seleccionar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;
        $('body').append(modalHtml);
    }

    // 2. Inyectar automáticamente botones "[ + Registrar Ciudadano ]" en los selectores de ciudadanos de la vista
    const citizenSelectSelectors = [
        '.select-citizen',
        'select#ciudadano_id',
        'select#padre_id',
        'select#madre_id',
        'select#contrayente_1_id',
        'select#contrayente_2_id',
        'select#ciudadano_1_id',
        'select#ciudadano_2_id',
        'select#reconocido_id',
        'select#reconocedor_id',
        'select#inscrito_id',
        'select#interesado_id',
        'select#titular_id'
    ];

    $(citizenSelectSelectors.join(', ')).each(function() {
        const $select = $(this);
        const selectId = $select.attr('id') || $select.attr('name');
        if (!selectId) return;

        // Evitar duplicar botón
        if ($select.closest('.mb-3, .col-md-6, .col-md-4, .col-12, .col-md-12').find(`.btn-quick-add-citizen[data-target-select="${selectId}"]`).length) {
            return;
        }

        // Buscar label asociado
        let $label = $(`label[for="${selectId}"]`);
        if (!$label.length) {
            $label = $select.prev('label');
        }
        if (!$label.length) {
            $label = $select.closest('.col-md-6, .col-md-4, .col-12, .mb-3').find('label').first();
        }

        const btnHtml = `<button type="button" class="btn-quick-add-citizen" data-target-select="${selectId}" title="Registrar nuevo ciudadano al padrón e insertarlo en este campo">
            <i class="fa-solid fa-user-plus"></i> + Registrar Ciudadano
        </button>`;

        if ($label.length) {
            $label.append(btnHtml);
        } else {
            $select.before(btnHtml);
        }
    });

    // 3. Regex oficial de CURP para validación en tiempo real dentro del modal
    const regexCurp = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/;
    $('#quick_curp').on('input', function() {
        const curp = $(this).val().trim().toUpperCase();
        const $icon = $('#quickCurpFeedback');
        const $help = $('#quickCurpHelp');

        if (curp.length === 0) {
            $(this).removeClass('is-valid is-invalid');
            $icon.addClass('d-none');
            $help.text('Dejar vacío si es recién nacido o alta de CURP.').removeClass('text-danger text-success');
        } else if (curp.length === 18 && regexCurp.test(curp)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $icon.removeClass('d-none').html('<i class="fa-solid fa-check text-success"></i>');
            $help.text('CURP con estructura oficial válida.').removeClass('text-danger').addClass('text-success fw-bold');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $icon.removeClass('d-none').html('<i class="fa-solid fa-triangle-exclamation text-danger"></i>');
            $help.text(`Formato incompleto o no válido (${curp.length}/18 caracteres).`).removeClass('text-success').addClass('text-danger fw-bold');
        }
    });

    // 4. Abrir modal al hacer clic en el botón "+ Registrar Ciudadano"
    $(document).on('click', '.btn-quick-add-citizen', function(e) {
        e.preventDefault();
        const targetSelectId = $(this).data('target-select');
        $('#quick_target_select').val(targetSelectId);

        // Obtener CSRF token del formulario principal
        const mainCsrf = $('input[name="csrf_token"]').first().val() || '';
        $('#quick_csrf_token').val(mainCsrf);

        // Limpiar campos del modal
        $('#formQuickCiudadano')[0].reset();
        $('#quick_curp').removeClass('is-valid is-invalid');
        $('#quickCurpFeedback').addClass('d-none');
        $('#quickCurpHelp').text('Dejar vacío si es recién nacido o alta de CURP.').removeClass('text-danger text-success');

        const modalEl = document.getElementById('modalQuickCiudadano');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
            setTimeout(() => $('#quick_nombre').focus(), 400);
        }
    });

    // 5. Envío y Registro AJAX
    $('#formQuickCiudadano').on('submit', function(e) {
        e.preventDefault();

        const nombre = $('#quick_nombre').val().trim().toUpperCase();
        const apePat = $('#quick_apellido_paterno').val().trim().toUpperCase();
        const apeMat = $('#quick_apellido_materno').val().trim().toUpperCase();
        const curp = $('#quick_curp').val().trim().toUpperCase();
        const sexo = $('#quick_sexo').val();
        const fechaNac = $('#quick_fecha_nacimiento').val();
        const csrfToken = $('#quick_csrf_token').val() || $('input[name="csrf_token"]').first().val();
        const targetSelectId = $('#quick_target_select').val();

        if (nombre.length < 2 || apePat.length < 2) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Ingrese el nombre y primer apellido completos.', confirmButtonColor: 'var(--secondary-color)' });
            } else {
                alert('Ingrese el nombre y primer apellido completos.');
            }
            return;
        }

        if (curp.length > 0 && (curp.length !== 18 || !regexCurp.test(curp))) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'CURP Inválida', text: 'La CURP debe tener 18 caracteres válidos según formato RENAPO o dejarse vacía.', confirmButtonColor: 'var(--secondary-color)' });
            } else {
                alert('La CURP debe tener 18 caracteres válidos o dejarse vacía.');
            }
            return;
        }

        const $btn = $('#btnQuickGuardar');
        const originalBtnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...');

        const saveUrl = window.location.pathname.includes('/modules/') 
            ? '../ciudadanos/save.php' 
            : 'modules/ciudadanos/save.php';

        $.ajax({
            url: saveUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                csrf_token: csrfToken,
                nombre: nombre,
                apellido_paterno: apePat,
                apellido_materno: apeMat,
                curp: curp,
                sexo: sexo,
                fecha_nacimiento: fechaNac
            },
            success: function(res) {
                $btn.prop('disabled', false).html(originalBtnHtml);

                if (res && res.status === 'success') {
                    const nuevoId = res.id;
                    const nuevoText = res.text || `${nombre} ${apePat} ${apeMat}` + (curp ? ` - CURP: ${curp}` : '');

                    // Buscar el elemento select de destino
                    const targetSelector = targetSelectId.startsWith('#') ? targetSelectId : `#${targetSelectId}`;
                    const $target = $(targetSelector);

                    if ($target.length) {
                        const domSelect = $target[0];
                        if (domSelect.tomselect) {
                            // Soporte TomSelect
                            domSelect.tomselect.addOption({
                                id: nuevoId,
                                text: nuevoText,
                                nombre_completo: `${nombre} ${apePat} ${apeMat}`,
                                curp: curp || 'S/C'
                            });
                            domSelect.tomselect.setValue(nuevoId);
                        } else {
                            // Select nativo / Select2
                            $target.append(new Option(nuevoText, nuevoId, true, true)).val(nuevoId).trigger('change');
                        }
                    }

                    // Cerrar modal
                    const modalEl = document.getElementById('modalQuickCiudadano');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                    }

                    // Notificación Toast de éxito
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Ciudadano registrado y seleccionado',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    const errMsg = (res && res.message) ? res.message : 'No se pudo registrar el ciudadano.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: errMsg, confirmButtonColor: 'var(--primary-color)' });
                    } else {
                        alert(errMsg);
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                let msg = 'Error de comunicación con el servidor al registrar ciudadano.';
                try {
                    const json = JSON.parse(xhr.responseText);
                    if (json && json.message) msg = json.message;
                } catch(e) {}
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonColor: 'var(--primary-color)' });
                } else {
                    alert(msg);
                }
            }
        });
    });
}

// Inicializar al cargar el documento
$(document).ready(function() {
    initQuickCitizenModal();
});


