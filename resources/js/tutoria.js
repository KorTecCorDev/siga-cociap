/**
 * tutoria.js — SIGA-COCIAP
 * Panel del tutor: guarda conclusiones transversales y cierra el bimestre.
 */

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';

function mostrarEstadoTutoria(tipo, mensaje) {
    const el = document.getElementById('tutoria-status');
    if (!el) return;
    el.textContent = mensaje;
    el.className   = `form-notas__status status--${tipo}`;
    if (tipo === 'success') {
        setTimeout(() => { el.textContent = ''; }, 4000);
    }
}

async function guardarConclusionesTransversales(periodoId) {
    const textareas = document.querySelectorAll('.textarea-conclusion-transversal');
    let guardadas = 0;
    let errores   = 0;

    for (const ta of textareas) {
        const texto = ta.value.trim();
        if (texto === (ta.dataset.guardada ?? '') && texto === ta.defaultValue.trim()) {
            continue; // sin cambios
        }

        const formData = new FormData();
        formData.append('_csrf_token',    CSRF);
        formData.append('matricula_id',   ta.dataset.matriculaId);
        formData.append('competencia_id', ta.dataset.competenciaId);
        formData.append('conclusion',     texto);

        try {
            const res  = await fetch(`${BASE}/docente/tutoria/${periodoId}/conclusion`,
                { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                ta.dataset.guardada = texto;
                ta.defaultValue     = texto;
                guardadas++;
            } else {
                errores++;
            }
        } catch (err) {
            errores++;
        }
    }

    if (errores > 0) {
        mostrarEstadoTutoria('error', `⚠ ${errores} conclusión(es) no se pudieron guardar.`);
    } else if (guardadas > 0) {
        mostrarEstadoTutoria('success', `✓ ${guardadas} conclusión(es) guardada(s).`);
    } else {
        mostrarEstadoTutoria('warning', 'No hay cambios que guardar.');
    }
    return errores === 0;
}

document.getElementById('btn-guardar-conclusiones-trans')
    ?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        btn.disabled = true;
        await guardarConclusionesTransversales(btn.dataset.periodoId);
        btn.disabled = false;
    });

document.getElementById('btn-cerrar-transversal')
    ?.addEventListener('click', async (e) => {
        const btn       = e.currentTarget;
        const periodoId = btn.dataset.periodoId;

        // Validación rápida en cliente (el servidor revalida todo)
        const vacias = Array.from(
            document.querySelectorAll('.textarea-conclusion-transversal[data-obligatorio="1"]')
        ).filter(ta => ta.value.trim() === '');

        if (vacias.length > 0) {
            mostrarEstadoTutoria('error',
                `⚠ Falta(n) ${vacias.length} conclusión(es) obligatoria(s).`);
            vacias[0].focus();
            return;
        }

        if (!confirm(
            '¿Cerrar el bimestre transversal?\n\n' +
            'Las notas TIC/GAMA y sus conclusiones aparecerán en las boletas. ' +
            'Solo el director podrá reabrir (desbloqueando una carga de la sección).'
        )) return;

        btn.disabled = true;
        mostrarEstadoTutoria('loading', 'Guardando y cerrando...');

        // Guardar conclusiones pendientes antes del cierre
        const okGuardado = await guardarConclusionesTransversales(periodoId);
        if (!okGuardado) {
            btn.disabled = false;
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_csrf_token', CSRF);

            const res  = await fetch(`${BASE}/docente/tutoria/${periodoId}/cerrar`,
                { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                mostrarEstadoTutoria('success', '✓ ' + data.mensaje);
                setTimeout(() => window.location.reload(), 900);
            } else {
                mostrarEstadoTutoria('error', '⚠ ' + data.mensaje);
                btn.disabled = false;
            }
        } catch (err) {
            mostrarEstadoTutoria('error', 'Error de conexión.');
            btn.disabled = false;
        }
    });
