/**
 * calificaciones.js — SIGA-COCIAP
 * Lógica del panel de calificaciones del docente.
 */

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const BASE = document.querySelector('meta[name="base-url"]')?.content ?? '';

// ── Acordeones de criterios ──────────────────────────────────
document.querySelectorAll('.criterio-bloque').forEach(bloque => {
    const header = bloque.querySelector('.criterio-bloque__header');
    header.addEventListener('click', (e) => {
        if (e.target.closest('button') || e.target.tagName === 'INPUT') return;
        const abriendo = !bloque.classList.contains('criterio-bloque--open');
        bloque.classList.toggle('criterio-bloque--open');
        if (abriendo) {
            setTimeout(() => bloque.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 260);
        }
    });
});

// ── Pegado masivo desde Excel / portapapeles ─────────────────
// Al copiar una columna en Excel y pegarla sobre cualquier input-nota,
// distribuye cada línea en el input correspondiente hacia abajo.
document.querySelectorAll('.form-notas').forEach(form => {
    form.addEventListener('paste', (e) => {
        if (!e.target.classList.contains('input-nota')) return;

        const raw    = (e.clipboardData || window.clipboardData).getData('text');
        const lineas = raw.split(/\r?\n/).map(l => l.trim()).filter(l => l !== '');

        // Si es un solo valor, dejar el comportamiento nativo del input
        if (lineas.length <= 1) return;

        e.preventDefault();

        const inputs = Array.from(form.querySelectorAll('.input-nota'));
        const inicio = inputs.indexOf(e.target);
        let pegados  = 0;

        lineas.forEach((linea, i) => {
            const idx = inicio + i;
            if (idx >= inputs.length) return;

            // Si Excel copió varias columnas, solo usar la primera
            const celda = linea.split('\t')[0].trim();
            const num   = parseInt(celda, 10);

            if (!isNaN(num)) {
                const valida = Math.min(20, Math.max(0, num));
                inputs[idx].value = String(valida).padStart(2, '0');
                inputs[idx].classList.remove('input--error');
                inputs[idx].classList.add('input--pasted');
                setTimeout(() => inputs[idx].classList.remove('input--pasted'), 1200);
                pegados++;
            } else if (celda === '' || celda === '-' || celda === '—') {
                inputs[idx].value = '';
                inputs[idx].classList.remove('input--error');
            }
        });

        if (pegados > 0) {
            const status = form.querySelector('.form-notas__status');
            mostrarStatus(status, 'success',
                `✓ ${pegados} nota(s) pegada(s) — revisa y guarda`);
        }
    });
});

// ── Validación y formato de inputs de nota (0-20) ────────────
document.querySelectorAll('.input-nota').forEach(input => {

    // 1. Bloquear teclas no numéricas en tiempo real
    input.addEventListener('keydown', (e) => {
        const esControl = e.ctrlKey || e.metaKey;
        const esNavegacion = [
            'Backspace', 'Delete', 'Tab',
            'ArrowLeft', 'ArrowRight', 'Home', 'End',
        ].includes(e.key);
        if (esNavegacion || esControl) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    });

    // 2. Limpiar caracteres no numéricos que entren por pegado individual
    input.addEventListener('input', () => {
        const soloDigitos = input.value.replace(/\D/g, '');
        if (input.value !== soloDigitos) input.value = soloDigitos;
    });

    // 3. Al salir del campo: ajustar rango y aplicar cero inicial
    input.addEventListener('blur', () => {
        const val = input.value.trim();
        if (val === '') {
            input.classList.remove('input--error');
            return;
        }
        const nota = parseInt(val, 10);
        if (!isNaN(nota)) {
            // Clamp al rango válido
            const valida = Math.min(20, Math.max(0, nota));
            input.value = String(valida).padStart(2, '0');
            input.classList.remove('input--error');
        } else {
            input.value = '';
            input.classList.remove('input--error');
        }
    });
});

// ── Guardar notas ────────────────────────────────────────────
document.querySelectorAll('.form-notas').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const criterioId    = form.dataset.criterioId;
        const competenciaId = form.dataset.competenciaId;
        const cargaId       = form.dataset.cargaId;
        const status        = form.querySelector('.form-notas__status');
        const btn           = form.querySelector('button[type="submit"]');

        // Recopilar notas
        const inputs = form.querySelectorAll('.input-nota');
        const notas  = {};
        let valido   = true;

        inputs.forEach(input => {
            const nombre = input.name; // notas[matricula_id]
            const match  = nombre.match(/\[(\d+)\]/);
            if (!match) return;

            const val = input.value.trim();
            if (val === '') return; // nota vacía = no registrar

            const nota = parseInt(val);
            if (isNaN(nota) || nota < 0 || nota > 20) {
                input.classList.add('input--error');
                valido = false;
            } else {
                input.classList.remove('input--error');
                notas[match[1]] = nota;
            }
        });

        if (!valido) {
            mostrarStatus(status, 'error', 'Verifica que las notas sean entre 0 y 20.');
            return;
        }

        if (Object.keys(notas).length === 0) {
            mostrarStatus(status, 'warning', 'No hay notas que guardar.');
            return;
        }

        // Enviar al servidor
        btn.disabled = true;
        mostrarStatus(status, 'loading', 'Guardando...');

        try {
            const formData = new FormData();
            formData.append('_csrf_token',   CSRF);
            formData.append('criterio_id',   criterioId);
            formData.append('competencia_id',competenciaId);

            Object.entries(notas).forEach(([id, nota]) => {
                formData.append(`notas[${id}]`, nota);
            });

            const url = `${BASE}/docente/calificaciones/${cargaId}/guardar`;
            const res = await fetch(url, { method: 'POST', body: formData });

            const data = await res.json();

            if (data.success) {
                mostrarStatus(status, 'success', '✓ ' + data.mensaje);
            } else {
                mostrarStatus(status, 'error', '⚠ ' + data.mensaje);
            }
        } catch (err) {
            mostrarStatus(status, 'error', 'Error de conexión.');
        } finally {
            btn.disabled = false;
        }
    });
});

// ── Agregar criterio ─────────────────────────────────────────
document.querySelectorAll('.btn-agregar-criterio').forEach(btn => {
    btn.addEventListener('click', async () => {
        const cargaId       = btn.dataset.cargaId;
        const competenciaId = btn.dataset.competenciaId;
        const input         = btn.previousElementSibling;
        const nombre        = input.value.trim();

        if (!nombre) {
            input.focus();
            return;
        }

        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('_csrf_token',   CSRF);
            formData.append('carga_id',      cargaId);
            formData.append('competencia_id',competenciaId);
            formData.append('nombre',        nombre);

            const res = await fetch(`${BASE}/docente/criterios/crear`,
                { method: 'POST', body: formData }
            );
            const data = await res.json();

            if (data.success) {
                // Recargar la página para mostrar el nuevo criterio
                window.location.reload();
            } else {
                alert(data.mensaje);
            }
        } catch (err) {
            alert('Error de conexión.');
        } finally {
            btn.disabled = false;
        }
    });
});

// ── Renombrar criterio ───────────────────────────────────────
document.querySelectorAll('.btn-renombrar-criterio').forEach(btn => {
    btn.addEventListener('click', () => {
        const criterioId = btn.dataset.criterioId;
        const bloque     = document.getElementById(`criterio-${criterioId}`);
        const h4         = bloque.querySelector('.criterio-bloque__nombre');
        const acciones   = bloque.querySelector('.criterio-bloque__acciones');
        const nombreActual = h4.textContent.trim();

        const input = document.createElement('input');
        input.type      = 'text';
        input.value     = nombreActual;
        input.maxLength = 120;
        input.className = 'form-input criterio-nombre-input';

        const btnGuardar = document.createElement('button');
        btnGuardar.type      = 'button';
        btnGuardar.textContent = 'Guardar';
        btnGuardar.className = 'btn btn--primary btn--sm';

        const btnCancelar = document.createElement('button');
        btnCancelar.type      = 'button';
        btnCancelar.textContent = 'Cancelar';
        btnCancelar.className = 'btn btn--secondary btn--sm';

        const wrapper = document.createElement('div');
        wrapper.className = 'criterio-nombre-editar';
        wrapper.append(input, btnGuardar, btnCancelar);

        h4.hidden       = true;
        acciones.hidden = true;
        h4.parentElement.append(wrapper);
        input.focus();
        input.select();

        const cancelar = () => {
            h4.hidden       = false;
            acciones.hidden = false;
            wrapper.remove();
        };

        const guardar = async () => {
            const nuevo = input.value.trim();
            if (!nuevo) { input.focus(); return; }
            if (nuevo === nombreActual) { cancelar(); return; }

            btnGuardar.disabled = btnCancelar.disabled = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', CSRF);
                formData.append('nombre',      nuevo);

                const res  = await fetch(
                    `${BASE}/docente/criterios/${criterioId}/renombrar`,
                    { method: 'POST', body: formData }
                );
                const data = await res.json();

                if (data.success) {
                    h4.textContent = data.nombre;
                    const btnEliminar = acciones.querySelector('.btn-eliminar-criterio');
                    if (btnEliminar) btnEliminar.dataset.nombre = data.nombre;
                    cancelar();
                } else {
                    alert(data.mensaje);
                    btnGuardar.disabled = btnCancelar.disabled = false;
                }
            } catch (err) {
                alert('Error de conexión.');
                btnGuardar.disabled = btnCancelar.disabled = false;
            }
        };

        btnGuardar.addEventListener('click', guardar);
        btnCancelar.addEventListener('click', cancelar);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter')  { e.preventDefault(); guardar(); }
            if (e.key === 'Escape') { cancelar(); }
        });
    });
});

// ── Eliminar criterio ────────────────────────────────────────
document.querySelectorAll('.btn-eliminar-criterio').forEach(btn => {
    btn.addEventListener('click', async () => {
        const criterioId  = btn.dataset.criterioId;
        const nombre      = btn.dataset.nombre;
        const tieneCals   = btn.dataset.tieneCalificaciones === '1';

        const mensaje = tieneCals
            ? `¿Eliminar el criterio "${nombre}"?\n\n` +
              `Este criterio tiene notas registradas. ` +
              `El promedio de los alumnos será recalculado sin este criterio.\n\n` +
              `Los datos quedan guardados en el sistema para auditoría.`
            : `¿Eliminar el criterio "${nombre}"?`;

        if (!confirm(mensaje)) return;

        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('_csrf_token', CSRF);

            const res = await fetch(
                `${BASE}/docente/criterios/${criterioId}/eliminar`,
                { method: 'POST', body: formData }
            );
            const data = await res.json();

            if (data.success) {
                if (data.tenia_calificaciones) {
                    // Recargar para reflejar el promedio recalculado
                    window.location.reload();
                } else {
                    document.getElementById(`criterio-${criterioId}`)?.remove();
                }
            } else {
                alert(data.mensaje);
                btn.disabled = false;
            }
        } catch (err) {
            alert('Error de conexión.');
            btn.disabled = false;
        }
    });
});

// ── Helper: mostrar estado ───────────────────────────────────
function mostrarStatus(el, tipo, mensaje) {
    if (!el) return;
    el.textContent  = mensaje;
    el.className    = `form-notas__status status--${tipo}`;

    if (tipo === 'success') {
        setTimeout(() => { el.textContent = ''; }, 4000);
    }
}

// ── Guardar conclusión descriptiva ───────────────────────────
document.querySelectorAll('.btn-guardar-conclusion').forEach(btn => {
    btn.addEventListener('click', async () => {
        const cargaId       = btn.dataset.cargaId;
        const competenciaId = btn.dataset.competenciaId;
        const contenedor    = btn.closest('.conclusion-form');
        const textarea      = contenedor.querySelector('.textarea-conclusion');
        const status        = contenedor.querySelector('.conclusion-status');
        const conclusion    = textarea.value.trim();

        if (!conclusion) {
            status.textContent = '⚠ Escribe la conclusión antes de guardar.';
            status.className   = 'conclusion-status status--warning';
            return;
        }

        btn.disabled = true;
        status.textContent = 'Guardando...';
        status.className   = 'conclusion-status status--loading';

        try {
            const formData = new FormData();
            formData.append('_csrf_token',   CSRF);
            formData.append('carga_id',      cargaId);
            formData.append('competencia_id',competenciaId);
            formData.append('conclusion',    conclusion);

            const res  = await fetch(
                `${BASE}/docente/calificaciones/conclusion`,
                { method: 'POST', body: formData }
            );
            const data = await res.json();

            if (data.success) {
                status.textContent = '✓ Conclusión guardada.';
                status.className   = 'conclusion-status status--success';
                setTimeout(() => { status.textContent = ''; }, 4000);
            } else {
                status.textContent = '⚠ ' + data.mensaje;
                status.className   = 'conclusion-status status--error';
            }
        } catch (err) {
            status.textContent = 'Error de conexión.';
            status.className   = 'conclusion-status status--error';
        } finally {
            btn.disabled = false;
        }
    });
});
