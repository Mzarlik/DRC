/**
 * DrcSeguimiento — Modal flotante reutilizable de seguimiento de trámites.
 * Muestra el detalle de un registro, banner de estatus y acciones contextuales
 * con confirmación y motivo opcional/obligatorio. POST estándar JSON.
 *
 * Uso:
 *   DrcSeguimiento.open({
 *       titulo: 'Seguimiento de Acta',
 *       endpoint: 'update_status.php',
 *       id: row.id,
 *       csrf: csrfToken,
 *       campos: [['NÚMERO DE ACTA', row.numero_acta], ['NOMBRE', row.nombre]],
 *       estatus: row.estatus,
 *       banner: { PENDIENTE: {clase:'alert-warning', icono:'fa-hourglass-half', texto:'...'} },
 *       acciones: {
 *           PENDIENTE: [
 *               {key:'RECHAZAR', label:'Rechazar', icono:'fa-ban', clase:'btn-outline-danger',
 *                requiereMotivo:true, titulo:'¿Rechazar?', texto:'...'}
 *           ]
 *       },
 *       onSuccess: function(resp){ table.ajax.reload(null,false); }
 *   });
 */
window.DrcSeguimiento = (function() {
    let modalEl = null;
    let modalInstance = null;
    let cfg = null;

    function ensureModal() {
        if (modalEl) return;
        const html = `
        <div class="modal fade" id="drcSeguimientoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background: var(--primary-color) !important;">
                        <h5 class="modal-title fw-bold" id="drcSegTitulo"><i class="fa-solid fa-clipboard-check me-2"></i> Seguimiento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert py-2 px-3 mb-3" id="drcSegBanner"></div>
                        <dl class="row mb-3" id="drcSegCampos"></dl>
                        <div class="mb-2">
                            <label for="drcSegMotivo" class="form-label fw-bold">Motivo de la Acción <span class="text-danger">*</span></label>
                            <textarea class="form-control text-uppercase-input" id="drcSegMotivo" rows="2" placeholder="OBLIGATORIO PARA CANCELAR, RECHAZAR O REACTIVAR. SE REGISTRA EN AUDITORÍA."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-wrap gap-2 justify-content-end" id="drcSegAcciones"></div>
                </div>
            </div>
        </div>`;
        $('body').append(html);
        modalEl = document.getElementById('drcSeguimientoModal');
        modalInstance = new bootstrap.Modal(modalEl);
    }

    function renderBanner() {
        const b = (cfg.banner && cfg.banner[cfg.estatus]) || { clase: 'alert-secondary', icono: 'fa-circle-info', texto: '' };
        $('#drcSegBanner')
            .removeClass('alert-warning alert-success alert-danger alert-info alert-secondary')
            .addClass(b.clase)
            .html(`<i class="fa-solid ${b.icono} me-2"></i><strong>${cfg.estatus}</strong>${b.texto ? ' — ' + b.texto : ''}`);
    }

    function renderCampos() {
        let html = '';
        (cfg.campos || []).forEach(function(par) {
            html += `<dt class="col-sm-4 text-muted small">${par[0]}</dt><dd class="col-sm-8">${par[1] ?? '—'}</dd>`;
        });
        $('#drcSegCampos').html(html || '<dd class="col-sm-12 text-muted">Sin detalle disponible.</dd>');
    }

    function renderAcciones() {
        const lista = (cfg.acciones && cfg.acciones[cfg.estatus]) || [];
        let html = lista.map(function(a) {
            return `<button type="button" class="btn ${a.clase || 'btn-outline-primary'}" data-accion="${a.key}">
                        <i class="fa-solid ${a.icono || 'fa-bolt'} me-1"></i> ${a.label}
                    </button>`;
        }).join('');
        html += '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
        $('#drcSegAcciones').html(html);
    }

    function ejecutar(key) {
        const accion = ((cfg.acciones && cfg.acciones[cfg.estatus]) || []).find(function(a) { return a.key === key; });
        if (!accion) return;
        const motivo = $('#drcSegMotivo').val().trim();

        if (accion.requiereMotivo && motivo.length < 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Motivo requerido',
                text: 'Debe capturar un motivo de al menos 5 caracteres para continuar.',
                confirmButtonColor: 'var(--primary-color)'
            });
            $('#drcSegMotivo').focus();
            return;
        }

        Swal.fire({
            title: accion.titulo || ('¿Aplicar ' + accion.label + '?'),
            text: accion.texto || '',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'No, cancelar',
            confirmButtonColor: 'var(--primary-color)',
            cancelButtonColor: '#6c757d'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: cfg.endpoint,
                type: 'POST',
                data: { id: cfg.id, accion: key, motivo: motivo, csrf_token: cfg.csrf },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        modalInstance.hide();
                        window.showToast('success', '¡Listo!', response.message);
                        if (typeof cfg.onSuccess === 'function') cfg.onSuccess(response);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: 'var(--primary-color)' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'No se pudo conectar con el servidor.', confirmButtonColor: 'var(--primary-color)' });
                }
            });
        });
    }

    $(document).on('click', '#drcSegAcciones button[data-accion]', function() {
        ejecutar($(this).data('accion'));
    });

    function open(options) {
        cfg = options;
        ensureModal();
        $('#drcSegTitulo').html(`<i class="fa-solid fa-clipboard-check me-2"></i> ${options.titulo || 'Seguimiento'}`);
        $('#drcSegMotivo').val('');
        renderCampos();
        renderBanner();
        renderAcciones();
        modalInstance.show();
    }

    return { open: open };
})();
