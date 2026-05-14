// ── Sistema genérico de modal ─────────────────────────────────
// Uso: Modal.abrir('id-del-overlay') / Modal.cerrar('id-del-overlay')
// Requiere: .modal-overlay con atributo hidden, .modal-box como hijo.
// Cierre automático: clic en el fondo oscuro, tecla Escape,
//   o botón con data-modal-cerrar="id-del-overlay".

window.Modal = {
    abrir(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.removeAttribute('hidden');
        overlay.classList.remove('modal--saliendo');
        overlay.classList.add('modal--activo');
        document.body.classList.add('modal-abierto');
        var primero = overlay.querySelector(
            'select, input:not([type="hidden"]), textarea, button:not(.modal-cerrar)'
        );
        if (primero) setTimeout(function() { primero.focus(); }, 40);
    },

    cerrar(id) {
        var overlay = document.getElementById(id);
        if (!overlay || !overlay.classList.contains('modal--activo')) return;
        overlay.classList.add('modal--saliendo');

        var terminado = false;
        var cleanup = function() {
            if (terminado) return;
            terminado = true;
            overlay.classList.remove('modal--activo', 'modal--saliendo');
            overlay.setAttribute('hidden', '');
            document.body.classList.remove('modal-abierto');
        };

        var onEnd = function(e) {
            if (e.target !== overlay) return;
            overlay.removeEventListener('animationend', onEnd);
            cleanup();
        };
        overlay.addEventListener('animationend', onEnd);
        setTimeout(cleanup, 300);
    }
};

// Cerrar al hacer clic en el fondo oscuro o en botón [data-modal-cerrar]
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        Modal.cerrar(e.target.id);
        return;
    }
    var btn = e.target.closest('[data-modal-cerrar]');
    if (btn) Modal.cerrar(btn.dataset.modalCerrar);
});

// Cerrar con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    var abierto = document.querySelector('.modal-overlay.modal--activo');
    if (abierto) Modal.cerrar(abierto.id);
});

// Estado de carga al enviar formularios dentro de modales
document.addEventListener('submit', function(e) {
    if (e.defaultPrevented) return;
    var form = e.target;
    if (!form.closest('.modal-overlay')) return;
    var btn = form.querySelector('[type="submit"]');
    if (!btn) return;
    btn.disabled = true;
    btn.textContent = 'Guardando…';
}, true);
