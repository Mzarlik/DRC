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
                    } else if (label.toLowerCase() === 'nombre' || label.toLowerCase() === 'nombre completo' || label.toLowerCase() === 'ciudadano' || label.toLowerCase() === 'usuario') {
                        cardTitle = valHtml;
                    } else if (label.toLowerCase() === 'id' || label.toLowerCase() === 'no. acta' || label.toLowerCase() === 'número de acta' || label.toLowerCase() === 'numero_acta' || label.toLowerCase() === 'clave') {
                        cardBadge = valText;
                    } else {
                        cardDetails.push({ label: label, value: valHtml });
                    }
                });

                // If we didn't find a specific title, use the first detail
                if (!cardTitle && cardDetails.length > 0) {
                    const nameIdx = cardDetails.findIndex(d => d.label.toLowerCase().includes('nombre') || d.label.toLowerCase().includes('usuario'));
                    if (nameIdx !== -1) {
                        cardTitle = cardDetails[nameIdx].value;
                        cardDetails.splice(nameIdx, 1);
                    } else {
                        cardTitle = cardDetails[0].value;
                        cardDetails.shift();
                    }
                }

                // Build Card HTML
                let cardHtml = `
                    <div class="card mobile-record-card mb-3 border-1">
                        <div class="card-header-mobile d-flex justify-content-between align-items-center p-3 border-bottom">
                            <div class="fw-bold text-primary-theme small-title">${cardTitle}</div>
                            ${cardBadge ? `<span class="badge bg-secondary-theme">${cardBadge}</span>` : ''}
                        </div>
                        <div class="card-body p-3">
                `;

                cardDetails.forEach(function(detail) {
                    cardHtml += `
                        <div class="card-detail-row d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom border-dashed">
                            <span class="text-muted small fw-semibold me-3">${detail.label}</span>
                            <span class="text-end text-dark-theme font-medium">${detail.value}</span>
                        </div>
                    `;
                });

                if (cardActions) {
                    cardHtml += `
                        <div class="card-actions-row mt-3 pt-3 border-top text-center">
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
            <div class="view-switcher-container d-flex justify-content-end mb-3 d-md-none">
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

    // 6. REUBICACIÓN DE BOTONES DE ACCIÓN EN MÓVILES (FAB)
    function setupMobileActionButtons() {
        if ($(window).width() < 768) {
            const $headerFlex = $('.container-fluid > .d-flex.justify-content-between.align-items-center');
            if ($headerFlex.length) {
                const $actionDiv = $headerFlex.find('div:has(.btn)');
                if ($actionDiv.length && !$actionDiv.hasClass('mobile-action-bar-processed')) {
                    $actionDiv.addClass('mobile-action-bar-processed');
                    
                    const $mobileBar = $('<div class="mobile-action-bar d-md-none"></div>');
                    $actionDiv.children().appendTo($mobileBar);
                    $('body').append($mobileBar);
                    $actionDiv.addClass('d-none');
                    $('body').css('padding-bottom', '95px');
                }
            }
        } else {
            const $actionDiv = $('.mobile-action-bar-processed');
            const $mobileBar = $('.mobile-action-bar');
            if ($actionDiv.length && $mobileBar.length) {
                $mobileBar.children().appendTo($actionDiv);
                $mobileBar.remove();
                $actionDiv.removeClass('d-none mobile-action-bar-processed');
                $('body').css('padding-bottom', '');
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

