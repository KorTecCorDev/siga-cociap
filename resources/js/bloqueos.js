/**
 * bloqueos.js — Acordeones de secciones en gestion de bloqueos
 */

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.bloqueo-seccion__header').forEach(btn => {
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
