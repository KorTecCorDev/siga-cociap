/**
 * print-fit.js — Ajuste de viewport para documentos A4 vistos en pantalla.
 *
 * Las vistas de impresion (boleta, reporte de merito) estan disenadas a un
 * ancho A4 fijo (mm/pt). En el celular, `width=device-width` aplasta ese
 * ancho dentro de ~390px y el contenido se encima. Este script mide el ancho
 * REAL de la hoja y fija el viewport a ese ancho, de modo que el telefono la
 * muestre completa y ajustada (igual que al abrir un PDF).
 *
 * - Solo afecta la PANTALLA: el viewport no influye en la impresion (@page)
 *   ni en el PDF guardado.
 * - No modifica estilos de forma permanente (mide con max-content y restaura).
 * - Si la hoja cabe en la pantalla, deja el comportamiento normal.
 */
(function () {
    var meta = document.querySelector('meta[name="viewport"]');
    if (!meta) { return; }

    var sheet = document.querySelector('.boleta-body') || document.body;
    if (!sheet) { return; }

    // Mide el ancho natural de la hoja sin dejar el estilo aplicado.
    function anchoNatural() {
        var prev = sheet.style.width;
        sheet.style.width = 'max-content';
        var w = Math.max(sheet.offsetWidth, sheet.scrollWidth);
        sheet.style.width = prev;
        return w;
    }

    function ajustar() {
        var w = anchoNatural();
        var pantalla = (window.screen && window.screen.width) || window.innerWidth;

        if (!w || w <= pantalla) {
            // La hoja cabe: comportamiento normal.
            meta.setAttribute('content', 'width=device-width, initial-scale=1.0');
        } else {
            // La hoja es mas ancha que la pantalla: el navegador la ajusta a lo ancho.
            meta.setAttribute('content', 'width=' + w);
        }
    }

    ajustar();
    window.addEventListener('load', ajustar);
    window.addEventListener('orientationchange', function () {
        setTimeout(ajustar, 200);
    });
})();

/**
 * Boton "Cerrar" del documento.
 *
 * Las boletas/reportes A4 se abren en ventana nueva por script (window.open en
 * app.js), asi que window.close() las cierra de forma fiable y se vuelve a la
 * ventana de origen. Fallback por si la ventana NO fue abierta por script (el
 * usuario abrio la pestana a mano) y el navegador bloquea close(): historial ->
 * referrer del mismo origen -> inicio (base-url).
 */
(function () {
    var cerrar = document.querySelector('.btn-boleta--cerrar');
    if (!cerrar) { return; }

    cerrar.addEventListener('click', function (e) {
        e.preventDefault();

        // Ventana abierta por script: cierra directo (la pagina desaparece y el
        // setTimeout de abajo nunca corre).
        window.close();

        // Si seguimos aqui, close() fue bloqueado: degradar.
        setTimeout(function () {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            var ref = document.referrer;
            if (ref) {
                try {
                    if (new URL(ref).origin === window.location.origin) {
                        window.location.href = ref;
                        return;
                    }
                } catch (err) { /* referrer invalido: seguir al fallback */ }
            }
            var meta = document.querySelector('meta[name="base-url"]');
            window.location.href = (meta && meta.getAttribute('content')) || '/';
        }, 120);
    });
})();
