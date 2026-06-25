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
 * Boton "Volver" robusto.
 *
 * Las boletas/reportes A4 se abren con target="_blank" (pestana nueva), donde
 * NO hay historial: history.back() no hace nada (se nota sobre todo en el
 * celular). Degradamos: historial si existe -> referrer del mismo origen ->
 * cerrar la pestana -> inicio (base-url). Si el JS no carga, el href original
 * (javascript:history.back) sigue como fallback minimo.
 */
(function () {
    var volver = document.querySelector('.btn-boleta--volver');
    if (!volver) { return; }

    function baseUrl() {
        var meta = document.querySelector('meta[name="base-url"]');
        return (meta && meta.getAttribute('content')) || '/';
    }

    volver.addEventListener('click', function (e) {
        e.preventDefault();

        // Caso normal (misma pestana): hay a donde retroceder.
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        // Pestana nueva: volver a la pagina de origen si es del mismo sitio.
        var ref = document.referrer;
        if (ref) {
            try {
                if (new URL(ref).origin === window.location.origin) {
                    window.location.href = ref;
                    return;
                }
            } catch (err) { /* referrer invalido: seguir al fallback */ }
        }

        // Sin historial ni referrer util: intentar cerrar; si el navegador lo
        // bloquea (rel=noopener), ir al inicio.
        window.close();
        setTimeout(function () { window.location.href = baseUrl(); }, 150);
    });
})();
