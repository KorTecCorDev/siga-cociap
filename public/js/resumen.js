/**
 * resumen.js — SIGA-COCIAP
 * Lógica de la vista de resumen de competencia.
 */

const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const BASE   = document.querySelector('meta[name="base-url"]')?.content ?? '';
const status = document.getElementById('resumen-status');

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
        const conclusion    = textarea.value.trim();

        try {
            const formData = new FormData();
            formData.append('_csrf_token',   CSRF);
            formData.append('matricula_id',  matriculaId);
            formData.append('conclusion',    conclusion);

            const res  = await fetch(
                `${BASE}/docente/calificaciones/${cargaId}/conclusion/${competenciaId}`,
                { method: 'POST', body: formData }
            );
            const data = await res.json();

            if (data.success) {
                guardados++;
            } else {
                errores++;
            }
        } catch (err) {
            errores++;
        }
    }

    if (errores === 0) {
        mostrarStatus('success', `✓ ${guardados} conclusión(es) guardada(s).`);
    } else {
        mostrarStatus('error', `⚠ ${errores} error(es) al guardar.`);
    }
});

// ── Aprobar y bloquear ───────────────────────────────────────
document.getElementById('btn-aprobar-bloquear')
    ?.addEventListener('click', async () => {

    const btn           = document.getElementById('btn-aprobar-bloquear');
    const cargaId       = btn.dataset.cargaId;
    const competenciaId = btn.dataset.competenciaId;

    // Verificar conclusiones obligatorias
    const pendientes = document.querySelectorAll(
        '.fila-pendiente .textarea-conclusion-alumno'
    );

    const sinConclusion = Array.from(pendientes)
        .filter(t => t.value.trim() === '');

    if (sinConclusion.length > 0) {
        mostrarStatus('error',
            `⚠ Hay ${sinConclusion.length} alumno(s) con conclusión obligatoria pendiente.`
        );
        sinConclusion[0].focus();
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
        }
    } catch (err) {
        mostrarStatus('error', 'Error de conexión.');
        btn.disabled = false;
    }
});

function mostrarStatus(tipo, mensaje) {
    if (!status) return;
    status.textContent = mensaje;
    status.className   = `status--${tipo}`;
    status.style.fontSize = '13px';
    status.style.marginLeft = '12px';
}