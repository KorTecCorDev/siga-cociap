/**
 * resumen.js — SIGA-COCIAP
 * Lógica de la vista de resumen de competencia.
 */

const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const BASE   = document.querySelector('meta[name="base-url"]')?.content ?? '';
const status = document.getElementById('resumen-status');

// ── Validación de conclusiones descriptivas ───────────────────

const MIN_CONCLUSION = 10;

function validarConclusion(valor) {
    const limpio = valor.trim().replace(/\s+/g, ' ');
    if (limpio.length === 0) {
        return { ok: false, msg: 'No puede estar vacía.' };
    }
    if (limpio.length < MIN_CONCLUSION) {
        return { ok: false, msg: `Mínimo ${MIN_CONCLUSION} caracteres (${limpio.length} escritos).` };
    }
    return { ok: true, msg: '' };
}

// Actualiza el resaltado de la fila según el valor actual del textarea
function actualizarFila(textarea) {
    const fila = textarea.closest('tr');
    if (!fila) return;
    const esPendiente = !validarConclusion(textarea.value).ok;
    fila.classList.toggle('fila-pendiente', esPendiente);
}

// Habilita/deshabilita el botón Aprobar según el estado de todas las obligatorias
function actualizarBotonAprobar() {
    const btn = document.getElementById('btn-aprobar-bloquear');
    if (!btn) return;

    const obligatorias = document.querySelectorAll(
        '.textarea-conclusion-alumno[data-obligatorio="1"]'
    );

    const hayInvalidas = Array.from(obligatorias).some(
        t => !validarConclusion(t.value).ok
    );

    btn.disabled = hayInvalidas || hayDatosNoGuardados;
    btn.title = hayInvalidas
        ? 'Completa todas las conclusiones obligatorias primero'
        : hayDatosNoGuardados
            ? 'Guarda las conclusiones antes de aprobar'
            : 'Aprobar y bloquear esta competencia';
}

// ── Seguimiento de cambios no guardados ───────────────────────

let hayDatosNoGuardados = false;

document.querySelectorAll('.textarea-conclusion-alumno').forEach(t => {
    t.dataset.valorInicial = t.value;
});

// ── Resaltado y control en tiempo real ───────────────────────

document.querySelectorAll('.textarea-conclusion-alumno[data-obligatorio="1"]')
    .forEach(textarea => {

        textarea.addEventListener('input', () => {
            hayDatosNoGuardados = true;
            actualizarFila(textarea);
            actualizarBotonAprobar();
        });

        textarea.addEventListener('blur', () => {
            // Normalizar espacios al salir del campo
            const normalizado = textarea.value.trim().replace(/\s+/g, ' ');
            if (textarea.value !== normalizado) textarea.value = normalizado;
            actualizarFila(textarea);
            actualizarBotonAprobar();
        });
    });

// Textareas opcionales: solo marcar datos no guardados
document.querySelectorAll('.textarea-conclusion-alumno[data-obligatorio="0"]')
    .forEach(textarea => {
        textarea.addEventListener('input', () => {
            hayDatosNoGuardados = true;
            actualizarBotonAprobar();
        });
    });

// ── Estado inicial al cargar la página ───────────────────────

document.querySelectorAll('.textarea-conclusion-alumno[data-obligatorio="1"]')
    .forEach(t => actualizarFila(t));

actualizarBotonAprobar();

// ── Guardar todas las conclusiones ───────────────────────────

document.getElementById('btn-guardar-conclusiones')
    ?.addEventListener('click', async () => {

    const textareas = document.querySelectorAll('.textarea-conclusion-alumno');
    let guardados   = 0;
    let errores     = 0;

    mostrarStatus('loading', 'Guardando conclusiones...');

    for (const textarea of textareas) {
        const matriculaId   = textarea.dataset.matriculaId;
        const cargaId       = textarea.dataset.cargaId;
        const competenciaId = textarea.dataset.competenciaId;

        // Normalizar antes de enviar
        textarea.value = textarea.value.trim().replace(/\s+/g, ' ');

        try {
            const formData = new FormData();
            formData.append('_csrf_token',  CSRF);
            formData.append('matricula_id', matriculaId);
            formData.append('conclusion',   textarea.value);

            const res  = await fetch(
                `${BASE}/docente/calificaciones/${cargaId}/conclusion/${competenciaId}`,
                { method: 'POST', body: formData }
            );
            const data = await res.json();

            if (data.success) {
                guardados++;
                textarea.dataset.valorInicial = textarea.value;
            } else {
                errores++;
            }
        } catch {
            errores++;
        }
    }

    if (errores === 0) {
        hayDatosNoGuardados = false;
        mostrarStatus('success', `✓ ${guardados} conclusión(es) guardada(s).`);
    } else {
        mostrarStatus('error', `⚠ ${errores} error(es) al guardar.`);
    }

    // Actualizar filas y botón según el estado real guardado
    document.querySelectorAll('.textarea-conclusion-alumno[data-obligatorio="1"]')
        .forEach(t => actualizarFila(t));
    actualizarBotonAprobar();
});

// ── Aprobar y bloquear ───────────────────────────────────────

document.getElementById('btn-aprobar-bloquear')
    ?.addEventListener('click', async () => {

    const btn           = document.getElementById('btn-aprobar-bloquear');
    const cargaId       = btn.dataset.cargaId;
    const competenciaId = btn.dataset.competenciaId;

    // Revalidar conclusiones obligatorias (defensa ante manipulación del DOM)
    const obligatorias = document.querySelectorAll(
        '.textarea-conclusion-alumno[data-obligatorio="1"]'
    );
    const invalidas = Array.from(obligatorias).filter(
        t => !validarConclusion(t.value).ok
    );

    if (invalidas.length > 0) {
        invalidas.forEach(t => actualizarFila(t));
        mostrarStatus('error',
            `⚠ ${invalidas.length} conclusión(es) obligatoria(s) incompleta(s) o con irregularidades.`
        );
        invalidas[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        invalidas[0].focus();
        return;
    }

    if (!confirm('¿Aprobar y bloquear esta competencia? Esta acción no se puede deshacer.')) {
        return;
    }

    mostrarStatus('loading', 'Bloqueando...');
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('_csrf_token', CSRF);

        const res  = await fetch(
            `${BASE}/docente/calificaciones/${cargaId}/bloquear/${competenciaId}`,
            { method: 'POST', body: formData }
        );
        const data = await res.json();

        if (data.success) {
            mostrarStatus('success', '✓ ' + data.mensaje);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            mostrarStatus('error', '⚠ ' + data.mensaje);
            btn.disabled = false;
            actualizarBotonAprobar();
        }
    } catch {
        mostrarStatus('error', 'Error de conexión.');
        btn.disabled = false;
        actualizarBotonAprobar();
    }
});

// ── Confirmar competencia no trabajada (sin criterios ni notas) ─

document.getElementById('btn-confirmar-sin-notas')
    ?.addEventListener('click', async () => {

    const btn           = document.getElementById('btn-confirmar-sin-notas');
    const cargaId       = btn.dataset.cargaId;
    const competenciaId = btn.dataset.competenciaId;

    if (!confirm(
        '¿Confirmar que esta competencia no fue trabajada en el bimestre?\n\n' +
        'Se bloqueará sin registrar calificaciones. Esta acción no se puede deshacer.'
    )) return;

    mostrarStatus('loading', 'Bloqueando...');
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('_csrf_token',       CSRF);
        formData.append('sin_calificaciones','1');

        const res  = await fetch(
            `${BASE}/docente/calificaciones/${cargaId}/bloquear/${competenciaId}`,
            { method: 'POST', body: formData }
        );
        const data = await res.json();

        if (data.success) {
            mostrarStatus('success', '✓ ' + data.mensaje);
            setTimeout(() => window.location.href = `${BASE}/docente/calificaciones/${cargaId}`, 1500);
        } else {
            mostrarStatus('error', '⚠ ' + data.mensaje);
            btn.disabled = false;
        }
    } catch {
        mostrarStatus('error', 'Error de conexión.');
        btn.disabled = false;
    }
});

// ── Helper: mostrar estado ───────────────────────────────────

function mostrarStatus(tipo, mensaje) {
    if (!status) return;
    status.textContent    = mensaje;
    status.className      = `status--${tipo}`;
    status.style.fontSize = '13px';
}
