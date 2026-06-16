/**
 * conducta.js — SIGA-COCIAP · ETAPA 1 (Registro Academico)
 * Grilla de criterios Si/No: toggle por criterio, nota RA en vivo (Si / total * 20,
 * redondeo a favor) y guardado por fila (los criterios son obligatorios).
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

function mostrarStatusFila(fila, tipo, mensaje, persistente = false) {
    const status = fila.querySelector('.conducta-status');
    if (!status) return;
    status.textContent = mensaje;
    status.className = `conducta-status status--${tipo}`;
    if (!persistente && tipo === 'success') {
        setTimeout(() => {
            if (status.textContent === mensaje) {
                status.textContent = '';
                status.className = 'conducta-status';
            }
        }, 2500);
    }
}

// Literal a partir de la nota (espejo de nota_a_literal en PHP: 18/14/11).
function literalDe(nota) {
    if (nota >= 18) return 'AD';
    if (nota >= 14) return 'A';
    if (nota >= 11) return 'B';
    return 'C';
}

// Nota RA en vivo: solo cuando los N criterios estan respondidos.
function recalcularNotaFila(fila) {
    const toggles = fila.querySelectorAll('.cc-toggle');
    const total   = parseInt(fila.dataset.total, 10) || toggles.length;
    let si = 0, respondidos = 0;
    toggles.forEach(t => {
        const v = t.dataset.valor;
        if (v === '1' || v === '0') respondidos++;
        if (v === '1') si++;
    });
    const span = fila.querySelector('.cc-nota');
    if (!span) return;
    if (respondidos < total) {
        span.textContent = '—';
        span.className = 'cc-nota';
    } else {
        const nota = Math.round((si / total) * 20); // RA siempre par con 10 criterios
        span.textContent = `${nota} (${literalDe(nota)})`;
        span.className = `cc-nota cc-nota--${literalDe(nota).toLowerCase()}`;
    }
}

function pintarToggle(toggle) {
    const v = toggle.dataset.valor;
    toggle.querySelectorAll('.cc-btn').forEach(b => {
        const activo = b.dataset.v === v;
        b.classList.toggle('cc-btn--activo', activo);
        b.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
}

// Marca visual del estado de guardado de la fila (dot + texto).
function marcarEstado(fila, estado) { // 'guardado' | 'pendiente'
    const guardado = estado === 'guardado';
    fila.classList.toggle('conducta-fila--guardada', guardado);
    fila.classList.toggle('conducta-fila--pendiente', !guardado);
    const txt = fila.querySelector('.conducta-estado-txt');
    if (txt) txt.textContent = guardado ? 'Guardado' : 'Sin guardar';
}

async function guardarFila(fila) {
    const toggles = [...fila.querySelectorAll('.cc-toggle')];
    const respuestas = {};
    let faltan = false;
    toggles.forEach(t => {
        const v = t.dataset.valor;
        if (v !== '1' && v !== '0') faltan = true;
        else respuestas[t.dataset.criterio] = v;
    });

    if (faltan) {
        mostrarStatusFila(fila, 'error', '⚠ Responde todos', true);
        mostrarFeedback('error', '⚠ Faltan criterios por responder en esta fila.');
        return;
    }

    const btn = fila.querySelector('.conducta-guardar');
    if (btn) btn.disabled = true;
    mostrarStatusFila(fila, 'loading', 'Guardando…', true);

    try {
        const body = new URLSearchParams();
        body.append('_csrf_token', fila.dataset.csrf);
        body.append('matricula_id', fila.dataset.matricula);
        body.append('periodo_id', fila.dataset.periodo);
        Object.entries(respuestas).forEach(([cid, v]) => body.append(`respuestas[${cid}]`, v));

        const res  = await fetch(URL_GUARDAR, { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            marcarEstado(fila, 'guardado');
            mostrarStatusFila(fila, 'success', '✓ Guardado');
            mostrarFeedback('ok', '✓ Guardado');
        } else {
            mostrarStatusFila(fila, 'error', '⚠ ' + (data.mensaje ?? 'Error'), true);
            mostrarFeedback('error', '⚠ ' + (data.mensaje ?? 'Error al guardar.'));
        }
    } catch (err) {
        mostrarStatusFila(fila, 'error', '⚠ Error de conexión', true);
        mostrarFeedback('error', '⚠ Error de conexión.');
    } finally {
        if (btn) btn.disabled = false;
    }
}

document.querySelectorAll('.conducta-fila').forEach(fila => {
    fila.querySelectorAll('.cc-toggle').forEach(toggle => {
        toggle.addEventListener('click', e => {
            const b = e.target.closest('.cc-btn');
            if (!b || b.disabled || !toggle.contains(b)) return;
            toggle.dataset.valor = b.dataset.v;
            pintarToggle(toggle);
            recalcularNotaFila(fila);
            marcarEstado(fila, 'pendiente'); // hay cambios sin guardar
        });
    });
    recalcularNotaFila(fila); // nota inicial al cargar
    fila.querySelector('.conducta-guardar')?.addEventListener('click', () => guardarFila(fila));
});
