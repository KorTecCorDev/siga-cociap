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

// ── Loader de transición entre páginas ───────────────────────
// Barra superior inmediata + overlay con logo si la navegación
// tarda más que OVERLAY_DELAY. Pensado para MPA (recarga completa):
// se muestra al salir de la página y desaparece al cargar la nueva.
(function() {
    var loader = document.getElementById('appLoader');
    if (!loader) return;

    var OVERLAY_DELAY  = 800;    // ms hasta mostrar el overlay con logo (solo en
                                 // navegaciones realmente lentas; la barra superior
                                 // ya da feedback inmediato en cada navegacion)
    var SAFETY_TIMEOUT = 8000;   // ms de seguridad por si no se navega
    var overlayTimer = null;
    var safetyTimer  = null;
    var navegando    = false;

    function iniciar() {
        if (navegando) return;
        navegando = true;
        loader.classList.add('is-active');                 // barra inmediata
        overlayTimer = setTimeout(function() {
            loader.classList.add('is-overlay');            // overlay si tarda
        }, OVERLAY_DELAY);
        safetyTimer = setTimeout(detener, SAFETY_TIMEOUT); // anti-trabado
    }

    function detener() {
        navegando = false;
        clearTimeout(overlayTimer);
        clearTimeout(safetyTimer);
        loader.classList.remove('is-active', 'is-overlay');
    }

    // ¿Este enlace provoca una navegación real del navegador?
    function debeNavegar(a) {
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#') return false;
        if (a.hasAttribute('download'))      return false;
        if (a.hasAttribute('data-no-loader')) return false;
        var target = a.getAttribute('target');
        if (target && target !== '_self')    return false;   // _blank, etc.
        if (/^(mailto:|tel:|javascript:)/i.test(href)) return false;
        if (a.origin && a.origin !== window.location.origin) return false;
        if (a.href === window.location.href) return false;   // misma URL
        // Solo cambia el hash dentro de la misma página → no recarga
        if (a.pathname === window.location.pathname &&
            a.search   === window.location.search   &&
            a.hash !== '') return false;
        return true;
    }

    document.addEventListener('click', function(e) {
        if (e.defaultPrevented) return;
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest('a');
        if (a && debeNavegar(a)) iniciar();
    });

    document.addEventListener('submit', function(e) {
        if (e.defaultPrevented) return;
        var form = e.target;
        if (form.hasAttribute('data-no-loader')) return;
        if ((form.getAttribute('target') || '_self') !== '_self') return;
        iniciar();
    });

    // Limpieza: al volver con "atrás" (bfcache) o al ocultar la página.
    window.addEventListener('pageshow', detener);
    window.addEventListener('pagehide', detener);
})();
