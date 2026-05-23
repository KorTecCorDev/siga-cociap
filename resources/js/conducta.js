/**
 * conducta.js — SIGA-COCIAP
 * Segmented control AD/A/B/C/limpiar para la vista admin/conducta/{seccion}.
 * El backend recibe literal vacío como "eliminar nota".
 */

const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';
const URL_GUARDAR = `${BASE}/admin/conducta/guardar`;

const feedback = document.getElementById('conducta-feedback');
let feedbackTimer;

function mostrarFeedback(tipo, mensaje) {
    if (!feedback) return;
    feedback.textContent = mensaje;
    feedback.className = `conducta-feedback conducta-feedback--${tipo}`;
    feedback.hidden = false;
    clearTimeout(feedbackTimer);
    feedbackTimer = setTimeout(() => { feedback.hidden = true; }, 3000);
}

function pintarActivo(control, literal) {
    control.querySelectorAll('.conducta-btn').forEach(btn => {
        // El botón de limpiar solo se considera activo cuando literal === ''
        const esLimpiar = btn.classList.contains('conducta-btn--clear');
        const lit       = btn.dataset.lit ?? '';
        const activo    = !esLimpiar && lit === literal;
        btn.classList.toggle('conducta-btn--activo', activo);
        btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
}

async function guardarConducta(control, nuevoLit) {
    const valorPrevio = control.dataset.valor ?? '';
    if (nuevoLit === valorPrevio) return;

    // Optimistic UI: pintamos el nuevo estado antes de la respuesta.
    pintarActivo(control, nuevoLit);
    control.classList.add('conducta-control--guardando');

    try {
        const res = await fetch(URL_GUARDAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                matricula_id: control.dataset.matricula,
                periodo_id:   control.dataset.periodo,
                literal:      nuevoLit,
                _csrf_token:  control.dataset.csrf,
            }),
        });
        const data = await res.json();

        if (data.success) {
            // Solo aquí confirmamos el nuevo valor como "previo" para
            // futuros rollbacks. Esto corrige el bug del data-original.
            control.dataset.valor = nuevoLit;
            mostrarFeedback('ok', nuevoLit === '' ? '✓ Nota eliminada' : '✓ Guardado');
        } else {
            pintarActivo(control, valorPrevio);
            mostrarFeedback('error', '⚠ ' + (data.mensaje ?? 'Error al guardar.'));
        }
    } catch (err) {
        pintarActivo(control, valorPrevio);
        mostrarFeedback('error', 'Error de conexión.');
    } finally {
        control.classList.remove('conducta-control--guardando');
    }
}

// Event delegation a nivel de cada control (el tbody puede tener cientos
// de botones; evitamos registrar un listener por botón).
document.querySelectorAll('.conducta-control').forEach(control => {
    control.addEventListener('click', e => {
        const btn = e.target.closest('.conducta-btn');
        if (!btn || btn.disabled) return;
        if (!control.contains(btn)) return;

        const nuevoLit = btn.dataset.lit ?? '';
        guardarConducta(control, nuevoLit);
    });
});
