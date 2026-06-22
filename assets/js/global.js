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

$(document).ready(function() {
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
        if ($(window).width() >= 768) {
            $('#sidebar').toggleClass('compact');
        } else {
            $('#sidebar').toggleClass('active');
        }
    });

    $('#sidebarCloseMobile').on('click', function () {
        $('#sidebar').removeClass('active');
    });

    // Expand sidebar if it's compact and user clicks a submenu toggle
    $('#sidebar').on('click', '.dropdown-toggle', function () {
        if ($('#sidebar').hasClass('compact')) {
            $('#sidebar').removeClass('compact');
        }
    });

    // 4. UTILITIES
    // Automatic conversion of uppercase inputs
    $(document).on('input', '.text-uppercase-input', function() {
        $(this).val($(this).val().toUpperCase());
    });
});
