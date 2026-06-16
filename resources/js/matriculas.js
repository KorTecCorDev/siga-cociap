/**
 * matriculas.js — Detalle de matrícula (show).
 * Desactivar: el motivo se despliega al pulsar "Desactivar" (disclosure).
 * Mejora progresiva: el HTML renderiza el formulario abierto; aquí lo
 * colapsamos y mostramos el disparador, así sin JS sigue funcionando.
 */

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-desactivar-form]').forEach((form) => {
        const bloque  = form.closest('.mat-accion');
        const control = bloque ? bloque.querySelector('[data-desactivar-control]') : null;
        const toggle  = bloque ? bloque.querySelector('[data-desactivar-toggle]') : null;
        const cancel  = form.querySelector('[data-desactivar-cancel]');
        const textarea = form.querySelector('textarea');
        if (!control || !toggle) return;

        // Estado inicial (mejorado por JS): colapsado.
        control.hidden = false;
        form.hidden    = true;
        if (cancel) cancel.hidden = false;

        toggle.addEventListener('click', () => {
            form.hidden    = false;
            control.hidden = true;
            if (textarea) textarea.focus();
        });

        if (cancel) {
            cancel.addEventListener('click', () => {
                form.hidden    = true;
                control.hidden = false;
                if (textarea) textarea.value = '';
            });
        }
    });
});
