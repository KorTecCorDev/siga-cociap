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

// Marca visual del estado de guardado de la fila (barra izquierda + color de fila).
function marcarEstado(fila, estado) { // 'guardado' | 'pendiente'
    const guardado = estado === 'guardado';
    fila.classList.toggle('conducta-fila--guardada', guardado);
    fila.classList.toggle('conducta-fila--pendiente', !guardado);
}

// true si la fila tiene los N criterios respondidos.
function filaCompleta(fila) {
    return [...fila.querySelectorAll('.cc-toggle')]
        .every(t => t.dataset.valor === '1' || t.dataset.valor === '0');
}

// Guarda una fila. En modo silencioso no dispara el toast global (lo usa el
// guardado masivo, que muestra un resumen agregado). Devuelve true si guardó.
async function guardarFila(fila, silencioso = false) {
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
        if (!silencioso) mostrarFeedback('error', '⚠ Faltan criterios por responder en esta fila.');
        return false;
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
            if (!silencioso) mostrarFeedback('ok', '✓ Guardado');
            return true;
        }
        mostrarStatusFila(fila, 'error', '⚠ ' + (data.mensaje ?? 'Error'), true);
        if (!silencioso) mostrarFeedback('error', '⚠ ' + (data.mensaje ?? 'Error al guardar.'));
        return false;
    } catch (err) {
        mostrarStatusFila(fila, 'error', '⚠ Error de conexión', true);
        if (!silencioso) mostrarFeedback('error', '⚠ Error de conexión.');
        return false;
    } finally {
        if (btn) btn.disabled = false;
    }
}

// Guarda en lote todas las filas pendientes y completas. Las pendientes con
// criterios sin responder se saltan (no se pueden guardar) y se informan.
async function guardarTodosPendientes(boton) {
    const pendientes = [...document.querySelectorAll('.conducta-fila--pendiente')];
    const completas  = pendientes.filter(filaCompleta);
    const incompletas = pendientes.length - completas.length;

    if (completas.length === 0) {
        mostrarFeedback(incompletas ? 'error' : 'ok',
            incompletas
                ? `⚠ ${incompletas} fila(s) pendiente(s) tienen criterios sin responder.`
                : 'No hay filas pendientes por guardar.');
        return;
    }

    if (boton) boton.disabled = true;
    let ok = 0, fail = 0;
    for (const fila of completas) {
        (await guardarFila(fila, true)) ? ok++ : fail++;
    }

    // Todo guardado, sin errores ni filas a medio llenar → refrescar para
    // mostrar los registros ya persistidos desde la BD.
    if (fail === 0 && incompletas === 0) {
        mostrarFeedback('ok', `✓ ${ok} fila(s) guardada(s). Actualizando…`);
        setTimeout(() => window.location.reload(), 700);
        return;
    }

    // Si quedaron filas sin completar, NO recargamos (perderían sus marcas).
    if (boton) boton.disabled = false;
    const extra = incompletas ? ` · ${incompletas} sin completar` : '';
    if (fail === 0) mostrarFeedback('ok', `✓ ${ok} guardada(s)${extra}. Completa las faltantes y vuelve a guardar.`);
    else            mostrarFeedback('error', `Guardadas ${ok}, con error ${fail}${extra}.`);
}

// Autollenar ✓ (Sí) en los criterios SIN responder de todas las filas.
// Manual y nunca destructivo: respeta toda marca previa (✓ o ✗). No guarda:
// deja las filas afectadas en estado pendiente para que el usuario revise y guarde.
function autollenarSi() {
    let celdas = 0, filas = 0;
    document.querySelectorAll('.conducta-fila').forEach(fila => {
        let cambiada = false;
        fila.querySelectorAll('.cc-toggle').forEach(toggle => {
            const v = toggle.dataset.valor;
            if (v === '1' || v === '0') return;            // ya respondida → no tocar
            if (toggle.querySelector('.cc-btn')?.disabled) return; // seccion bloqueada
            toggle.dataset.valor = '1';
            pintarToggle(toggle);
            celdas++;
            cambiada = true;
        });
        if (cambiada) {
            recalcularNotaFila(fila);
            marcarEstado(fila, 'pendiente');
            filas++;
        }
    });

    if (celdas === 0) {
        mostrarFeedback('ok', 'No quedaban criterios sin responder.');
    } else {
        mostrarFeedback('ok', `✓ ${celdas} criterio(s) marcados en ${filas} fila(s). Revisa las excepciones y guarda.`);
    }
}

document.getElementById('conducta-autollenar')?.addEventListener('click', () => {
    if (!confirm('¿Marcar ✓ (Sí) en todos los criterios sin responder?\nNo cambia las marcas existentes. Luego revisa las excepciones y guarda.')) return;
    autollenarSi();
});

document.getElementById('conducta-guardar-todos')?.addEventListener('click', e => {
    guardarTodosPendientes(e.currentTarget);
});

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
});
