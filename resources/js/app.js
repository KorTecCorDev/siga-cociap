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
// Dos animaciones en cadena para MPA (recarga completa):
//   1. Barra superior inmediata que se llena en BAR_FILL ms.
//   2. Al terminar la barra, si la espera sigue, escala al overlay
//      con logo, que ADEMÁS bloquea toda interacción de la vista
//      (puntero + teclado + lectores de pantalla) hasta que carga.
// Las descargas (attachment, que no navegan) solo muestran la barra
// y la ocultan solas: nunca escalan al overlay ni bloquean.
(function() {
    var loader = document.getElementById('appLoader');
    if (!loader) return;

    var BAR_FILL       = 800;    // ms que tarda la barra en llenarse (coincide con
                                 // la transición width del SASS); al terminar escala
    var DESCARGA_HIDE  = 1200;   // ms hasta ocultar la barra tras iniciar una descarga
    var SAFETY_TIMEOUT = 8000;   // ms de seguridad por si no se navega
    var overlayTimer = null;
    var safetyTimer  = null;
    var navegando    = false;

    // Bloquea/desbloquea todo lo que hay detrás del overlay: inert desactiva
    // clics, foco y selección de teclado y lo oculta a lectores de pantalla.
    function bloquearFondo(activar) {
        var hijos = document.body.children;
        for (var i = 0; i < hijos.length; i++) {
            if (hijos[i] === loader) continue;
            if (activar) hijos[i].setAttribute('inert', '');
            else         hijos[i].removeAttribute('inert');
        }
        if (activar) {
            loader.removeAttribute('aria-hidden');
            loader.setAttribute('aria-busy', 'true');
        } else {
            loader.setAttribute('aria-hidden', 'true');
            loader.removeAttribute('aria-busy');
        }
    }

    function escalarOverlay() {
        loader.classList.add('is-overlay');   // overlay con logo
        bloquearFondo(true);                   // y bloquea la vista de atrás
    }

    // soloBarra=true para descargas: barra corta, sin overlay ni bloqueo.
    function iniciar(soloBarra) {
        if (navegando) return;
        navegando = true;
        loader.classList.add('is-active');                     // barra inmediata
        if (soloBarra) {
            safetyTimer = setTimeout(detener, DESCARGA_HIDE);
            return;
        }
        overlayTimer = setTimeout(escalarOverlay, BAR_FILL);   // escala al terminar la barra
        safetyTimer  = setTimeout(detener, SAFETY_TIMEOUT);    // anti-trabado
    }

    function detener() {
        navegando = false;
        clearTimeout(overlayTimer);
        clearTimeout(safetyTimer);
        loader.classList.remove('is-active', 'is-overlay');
        bloquearFondo(false);
    }

    var TOAST_VIDA = 7000;   // ms que el aviso "descarga lista" permanece visible

    // Aviso in-app "descarga lista": no bloquea la vista (a diferencia del overlay).
    // Muestra el nombre del archivo y deja volver a descargar. El navegador no
    // permite abrir su panel de descargas por código; esto es el equivalente propio.
    // Reemplaza cualquier aviso anterior. Todo el texto entra por textContent (sin XSS).
    function mostrarToastDescarga(nombre, href) {
        var previo = document.getElementById('toastDescarga');
        if (previo && previo.parentNode) previo.parentNode.removeChild(previo);

        var toast = document.createElement('div');
        toast.id = 'toastDescarga';
        toast.className = 'toast-descarga';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');

        var icono = document.createElement('span');
        icono.className = 'toast-descarga__icono';
        icono.setAttribute('aria-hidden', 'true');
        icono.textContent = '✓';
        toast.appendChild(icono);

        var cuerpo = document.createElement('div');
        cuerpo.className = 'toast-descarga__cuerpo';

        var titulo = document.createElement('strong');
        titulo.className = 'toast-descarga__titulo';
        titulo.textContent = 'Descarga iniciada';
        cuerpo.appendChild(titulo);

        if (nombre) {
            var archivo = document.createElement('span');
            archivo.className = 'toast-descarga__archivo';
            archivo.textContent = nombre;
            cuerpo.appendChild(archivo);
        }

        var tip = document.createElement('span');
        tip.className = 'toast-descarga__tip';
        tip.textContent = 'La encuentras en tu carpeta de Descargas o con Ctrl+J.';
        cuerpo.appendChild(tip);

        if (href) {
            var accion = document.createElement('a');
            accion.className = 'toast-descarga__accion';
            accion.href = href;
            accion.setAttribute('data-descarga', nombre || '');  // reusa el mismo flujo
            accion.textContent = 'Volver a descargar';
            cuerpo.appendChild(accion);
        }

        toast.appendChild(cuerpo);

        var cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'toast-descarga__cerrar';
        cerrar.setAttribute('aria-label', 'Cerrar aviso');
        cerrar.textContent = '×';
        toast.appendChild(cerrar);

        document.body.appendChild(toast);
        // Doble rAF: pinta el estado inicial y recién entonces anima la entrada.
        requestAnimationFrame(function() {
            requestAnimationFrame(function() { toast.classList.add('is-visible'); });
        });

        var vidaTimer = null;
        function cerrarToast() {
            if (!toast.parentNode) return;
            clearTimeout(vidaTimer);
            toast.classList.remove('is-visible');
            toast.classList.add('is-saliendo');
            var quitado = false;
            var quitar = function() {
                if (quitado) return;
                quitado = true;
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            };
            toast.addEventListener('transitionend', quitar);
            setTimeout(quitar, 400);   // fallback si no hay transición
        }

        cerrar.addEventListener('click', cerrarToast);
        vidaTimer = setTimeout(cerrarToast, TOAST_VIDA);
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
        if (!a) return;
        // Descarga (attachment): solo barra, se oculta sola; no escala ni bloquea.
        // Al ocultarse la barra (la descarga ya arrancó) mostramos el aviso propio.
        // Si la descarga fallara, el servidor redirige → la página navega y este
        // setTimeout muere con ella: el aviso NO aparece en caso de error.
        if (a.hasAttribute('data-descarga')) {
            var nombreDescarga = a.getAttribute('data-descarga');
            var hrefDescarga   = a.href;
            iniciar(true);
            setTimeout(function() {
                mostrarToastDescarga(nombreDescarga, hrefDescarga);
            }, DESCARGA_HIDE);
            return;
        }
        if (debeNavegar(a)) iniciar();
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

// ── Documentos en ventana nueva: abrir por script (autocerrables) ─────
// Los documentos (boletas, reportes A4, nóminas) se abren con target="_blank".
// Si el navegador crea la pestaña, ESTA no fue abierta por script y su
// window.close() queda bloqueado → el botón "Cerrar" del documento no puede
// cerrarla y en el celular se acumulan pestañas (lentitud). Al abrirlas
// nosotros con window.open(), la ventana es autocerrable desde su botón
// "Cerrar". target="_blank" se conserva como fallback si este JS no carga.
(function () {
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented) return;
        // Respeta clic-medio / con modificadores (abrir en 2.º plano a voluntad).
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var a = e.target.closest('a[target="_blank"]');
        if (!a) return;

        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#') return;
        if (a.hasAttribute('download')) return;
        if (/^(mailto:|tel:|javascript:)/i.test(href)) return;
        // Solo documentos internos (mismo origen); externos → comportamiento nativo.
        if (a.origin && a.origin !== window.location.origin) return;

        e.preventDefault();
        window.open(a.href, '_blank');
    });
})();
