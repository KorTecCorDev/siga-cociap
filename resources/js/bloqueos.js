/**
 * bloqueos.js — Panel del director:
 *  1. Tabs (cards) que muestran/ocultan cada tipo de bloqueo SIN recargar.
 *     Sin detalle hasta hacer clic; recuerda el último tab abierto por
 *     periodo (sessionStorage) para no perder el contexto tras un POST.
 *  2. Acordeones de secciones académicas.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Tabs del hub ─────────────────────────────────────────────
    const cards   = Array.from(document.querySelectorAll('.bloqueos-tabcard'));
    const paneles = Array.from(document.querySelectorAll('.bloqueos-panel'));

    if (cards.length && paneles.length) {
        // Clave por periodo: así el tab recordado no se mezcla entre bimestres.
        const periodoId = new URLSearchParams(location.search).get('periodo_id') || '0';
        const storeKey  = 'bloqueos.tab.' + periodoId;

        const mostrar = (nombre) => {
            cards.forEach((c) => {
                const activo = c.dataset.tab === nombre;
                c.classList.toggle('bloqueos-tabcard--activa', activo);
                c.setAttribute('aria-selected', activo ? 'true' : 'false');
            });
            paneles.forEach((p) => {
                p.hidden = p.dataset.panel !== nombre;
            });
        };

        const ocultar = () => {
            cards.forEach((c) => {
                c.classList.remove('bloqueos-tabcard--activa');
                c.setAttribute('aria-selected', 'false');
            });
            paneles.forEach((p) => { p.hidden = true; });
        };

        cards.forEach((card) => {
            card.addEventListener('click', () => {
                const nombre = card.dataset.tab;
                const yaActiva = card.classList.contains('bloqueos-tabcard--activa');
                if (yaActiva) {
                    // Segundo clic en la card activa → colapsa (vuelve a "sin detalle").
                    ocultar();
                    sessionStorage.removeItem(storeKey);
                } else {
                    mostrar(nombre);
                    sessionStorage.setItem(storeKey, nombre);
                }
            });
        });

        // Restaurar el último tab del periodo (si su card existe).
        const guardado = sessionStorage.getItem(storeKey);
        if (guardado && cards.some((c) => c.dataset.tab === guardado)) {
            mostrar(guardado);
        }
    }

    // ── Acordeones de secciones académicas ───────────────────────
    document.querySelectorAll('.bloqueo-seccion__header').forEach((btn) => {
        btn.addEventListener('click', () => {
            const seccion = btn.closest('.bloqueo-seccion');
            const abierto = seccion.hasAttribute('data-open');
            if (abierto) {
                seccion.removeAttribute('data-open');
                btn.setAttribute('aria-expanded', 'false');
            } else {
                seccion.setAttribute('data-open', '');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
});
