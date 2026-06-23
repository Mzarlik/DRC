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

// Overwrite DataTables defaults to globally enable Responsive extension
if (window.jQuery && $.fn.dataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
        responsive: true
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
    if ($navbarRight.length && !$('#darkModeToggle').length) {
        const isDark = localStorage.getItem('theme') === 'dark';
        const iconClass = isDark ? 'fa-sun' : 'fa-moon';
        const toggleHtml = `
            <button type="button" id="darkModeToggle" class="btn btn-link text-dark nav-link me-3 p-0" title="Alternar Modo Oscuro" style="border: none; background: none; font-size: 1.25rem;">
                <i class="fa-solid ${iconClass}"></i>
            </button>
        `;
        // Prepend inside the right-aligned container of navbar
        $navbarRight.prepend(toggleHtml);
        
        // Make sure the toggle icon matches theme changes in other tabs/reloads
        if (isDark) {
            $('#darkModeToggle').addClass('text-light').removeClass('text-dark');
        }
    }

    // Toggle click handler
    $(document).on('click', '#darkModeToggle', function(e) {
        e.preventDefault();
        const $body = $('body');
        const $icon = $(this).find('i');
        const isDark = $body.hasClass('dark-mode');

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

    // 3. SIDEBAR CENTRALIZED LÓGICA
    $('#sidebarCollapse').on('click', function () {
        if ($(window).width() >= 992) {
            $('#sidebar').toggleClass('compact');
            setTimeout(() => {
                if ($.fn.dataTable) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }
            }, 300);
        } else {
            const sidebarEl = document.getElementById('sidebar');
            if (sidebarEl) {
                const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarEl);
                bsOffcanvas.toggle();
            }
        }
    });

    $('#sidebarCloseMobile').on('click', function () {
        if ($(window).width() < 992) {
            const sidebarEl = document.getElementById('sidebar');
            if (sidebarEl) {
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebarEl);
                if (bsOffcanvas) {
                    bsOffcanvas.hide();
                }
            }
        }
    });

    // Expand sidebar if it's compact and user clicks a submenu toggle
    $('#sidebar').on('click', '.dropdown-toggle', function () {
        if ($('#sidebar').hasClass('compact')) {
            $('#sidebar').removeClass('compact');
        }
    });

    // Clean up active offcanvas states if window is resized past 992px
    $(window).on('resize', function() {
        if ($(window).width() >= 992) {
            const sidebarEl = document.getElementById('sidebar');
            if (sidebarEl) {
                const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebarEl);
                if (bsOffcanvas) {
                    bsOffcanvas.hide();
                }
            }
        }
    });

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

