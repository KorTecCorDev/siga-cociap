/**
 * nomina.js — SIGA-COCIAP
 * Vista /docente/nomina:
 *   1. Selector de sección → habilita el botón para imprimir su nómina.
 *   2. Buscador en vivo de estudiantes (los resultados aparecen al escribir).
 * No maneja DNI: dato sensible, no se renderiza en esta vista.
 */
(function () {
    // ── 1. Selector de sección → botón imprimir ──────────────────
    const select = document.getElementById('nomina-seccion');
    const btn    = document.getElementById('nomina-imprimir-btn');

    if (select && btn) {
        const base = btn.dataset.base;

        function syncBtn() {
            const id = select.value;
            if (id) {
                btn.href = base + '/' + id + '/imprimir';
                btn.classList.remove('is-disabled');
                btn.removeAttribute('aria-disabled');
            } else {
                btn.href = '#';
                btn.classList.add('is-disabled');
                btn.setAttribute('aria-disabled', 'true');
            }
        }

        select.addEventListener('change', syncBtn);
        btn.addEventListener('click', function (e) {
            if (!select.value) e.preventDefault();
        });
        syncBtn();
    }

    // ── 2. Buscador en vivo ──────────────────────────────────────
    const input = document.getElementById('nomina-buscador');
    if (!input) return;

    const filas      = Array.from(document.querySelectorAll('.nomina-fila'));
    const resultados = document.getElementById('nomina-resultados');
    const sinResult  = document.getElementById('nomina-sin-resultados');
    const hint       = document.getElementById('nomina-hint');

    function normalizar(t) {
        return (t || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, ''); // quita tildes
    }

    function filtrar() {
        const q = normalizar(input.value.trim());

        // Sin texto: oculta resultados y muestra la pista inicial.
        if (q === '') {
            filas.forEach(function (f) { f.hidden = true; });
            if (resultados) resultados.hidden = true;
            if (sinResult)  sinResult.hidden = true;
            if (hint)       hint.hidden = false;
            return;
        }

        if (hint) hint.hidden = true;
        let visibles = 0;

        filas.forEach(function (f) {
            const match = normalizar(f.dataset.buscar).indexOf(q) !== -1;
            f.hidden = !match;
            if (match) {
                visibles++;
                const num = f.querySelector('.nomina-num');
                if (num) num.textContent = visibles;
            }
        });

        if (resultados) resultados.hidden = visibles === 0;
        if (sinResult)  sinResult.hidden = visibles !== 0;
    }

    input.addEventListener('input', filtrar);
    filtrar();
})();
