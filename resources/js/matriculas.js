/**
 * matriculas.js — Detalle de matrícula (show).
 * Disclosures con mejora progresiva: el HTML renderiza el formulario abierto;
 * aquí lo colapsamos y mostramos el disparador, así sin JS sigue funcionando.
 *  - Desactivar: despliega el motivo de la baja.
 *  - Editar datos: despliega el formulario de datos personales del estudiante.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Editar datos personales del estudiante.
    document.querySelectorAll('[data-editar-form]').forEach((form) => {
        const card    = form.closest('.card__body');
        const control = card ? card.querySelector('[data-editar-control]') : null;
        const toggle  = card ? card.querySelector('[data-editar-toggle]') : null;
        const cancel  = form.querySelector('[data-editar-cancel]');
        if (!control || !toggle) return;

        // Estado inicial (mejorado por JS): colapsado.
        control.hidden = false;
        form.hidden    = true;
        if (cancel) cancel.hidden = false;

        toggle.addEventListener('click', () => {
            form.hidden    = false;
            control.hidden = true;
            const primero = form.querySelector('input, select');
            if (primero) primero.focus();
        });

        if (cancel) {
            cancel.addEventListener('click', () => {
                form.reset();          // descarta lo tecleado, vuelve a los valores guardados
                form.hidden    = true;
                control.hidden = false;
            });
        }
    });

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
