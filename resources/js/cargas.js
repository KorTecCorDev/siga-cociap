/**
 * cargas.js — Formulario de carga academica
 * Filtrado de areas/subareas por nivel y disponibilidad,
 * hints de horario por seccion y docente, toggle de dias.
 */

document.addEventListener('DOMContentLoaded', () => {
    initDiasToggle();
    initBloques();
    initSinHorario();
    initAreaFilter();
    initDocenteHint();
});

// ── Sin horario propio ────────────────────────────────────────
// La carga se dicta dentro del horario de otra carga del área (subáreas con
// el mismo docente) o su horario aún no se registra. Al marcar el checkbox se
// desmarcan y deshabilitan los días; el servidor guarda la carga sin sesiones.

function initSinHorario() {
    const cb = document.getElementById('sin_horario');
    if (!cb) return;
    cb.addEventListener('change', aplicarSinHorario);
    // Inicializar (editar: viene marcado si la carga no tiene sesiones)
    aplicarSinHorario();
}

function aplicarSinHorario() {
    const cb = document.getElementById('sin_horario');
    if (!cb) return;
    const sin = cb.checked;

    document.querySelectorAll('.dia-check').forEach(dc => {
        if (sin && dc.checked) {
            dc.checked = false;
            toggleDia(dc); // colapsa rangos, limpia y deshabilita los inputs
        }
        dc.disabled = sin;
    });

    const grid = document.querySelector('.horario-grid');
    if (grid) grid.classList.toggle('horario-grid--deshabilitado', sin);
}

// ── Dias ──────────────────────────────────────────────────────

function initDiasToggle() {
    document.querySelectorAll('.dia-check').forEach(cb => {
        cb.addEventListener('change', () => toggleDia(cb));
        if (cb.checked) {
            const row = document.getElementById('dia-row-' + cb.value);
            row?.classList.add('dia-row--activo');
            if (row) refrescarQuitar(row);
        }
    });
}

function toggleDia(cb) {
    const row = document.getElementById('dia-row-' + cb.value);
    if (!row) return;
    const activo = cb.checked;

    // Al DESMARCAR el día: colapsar a un solo rango vacío (quita los clonados).
    if (!activo) {
        row.querySelectorAll('.bloque-rango').forEach((b, i) => { if (i > 0) b.remove(); });
    }

    row.querySelectorAll('input[type="time"]').forEach(input => {
        input.disabled = !activo;
        if (!activo) input.value = '';
    });

    const addBtn = row.querySelector('.bloque-agregar');
    if (addBtn) addBtn.disabled = !activo;

    refrescarQuitar(row);
    row.classList.toggle('dia-row--activo', activo);
}

// ── Bloques de horario por día (N rangos no consecutivos) ─────

function initBloques() {
    const grid = document.querySelector('.horario-grid');
    if (!grid) return;
    grid.addEventListener('click', (e) => {
        const add = e.target.closest('.bloque-agregar');
        if (add && !add.disabled) { agregarBloque(add.dataset.dia); return; }
        const del = e.target.closest('.bloque-quitar');
        if (del) { quitarBloque(del); }
    });
}

// El botón "Quitar" solo se ve cuando el día tiene más de un rango.
function refrescarQuitar(row) {
    const rangos = row.querySelectorAll('.bloque-rango');
    const varios = rangos.length > 1;
    rangos.forEach(r => {
        const q = r.querySelector('.bloque-quitar');
        if (q) q.hidden = !varios;
    });
}

// Clona el primer rango del día, lo deja vacío y habilitado, y lo inserta
// antes del hint (que precede al botón "Agregar bloque").
function agregarBloque(dia) {
    const cont = document.getElementById('bloques-' + dia);
    if (!cont) return;
    const base = cont.querySelector('.bloque-rango');
    if (!base) return;

    const nuevo = base.cloneNode(true);
    nuevo.querySelectorAll('input[type="time"]').forEach(i => { i.value = ''; i.disabled = false; });

    const hint = document.getElementById('hint-' + dia);
    cont.insertBefore(nuevo, hint);

    refrescarQuitar(document.getElementById('dia-row-' + dia));
}

function quitarBloque(btn) {
    const rango = btn.closest('.bloque-rango');
    const row   = btn.closest('.dia-row');
    if (!rango || !row) return;
    rango.remove();
    refrescarQuitar(row);
}

// ── Lectura de datos embebidos ────────────────────────────────

function getDato(clave) {
    const el = document.getElementById('cargasData');
    if (!el) return {};
    try { return JSON.parse(el.dataset[clave] || '{}'); } catch (e) { return {}; }
}

// ── Hints de horario ──────────────────────────────────────────

const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

/** Extrae la ultima hora_fin de los rangos de un docente en un dia. */
function ultimaFinDocente(docenteId, dia) {
    const bloques = (getDato('bloquesDocentes')[docenteId] || {})[dia] || [];
    if (!bloques.length) return null;
    // Cada rango es "HH:MM-HH:MM"; toma la parte derecha y queda con el max
    return bloques.reduce((max, rango) => {
        const fin = rango.split('-')[1] || '';
        return fin > max ? fin : max;
    }, '');
}

/**
 * Muestra "Libre desde HH:MM" usando el maximo entre la ultima hora
 * ocupada de la seccion y la ultima hora ocupada del docente.
 * Ambas restricciones deben cumplirse; el limite efectivo es el mayor.
 */
function actualizarHints() {
    const docenteSel = document.getElementById('docente_id');
    const seccionSel = document.getElementById('seccion_id');
    const docenteId  = docenteSel?.value || '';
    const seccionId  = seccionSel?.value  || '';

    DIAS.forEach(dia => {
        const hint = document.getElementById('hint-' + dia);
        if (!hint) return;

        const finSeccion  = (getDato('horarios')[seccionId] || {})[dia] || null;
        const finDocente  = docenteId ? ultimaFinDocente(docenteId, dia) : null;

        // Toma el mayor de los dos (comparacion lexicografica valida para HH:MM)
        const efectivo = finSeccion && finDocente
            ? (finSeccion > finDocente ? finSeccion : finDocente)
            : (finSeccion || finDocente);

        if (efectivo) {
            hint.className   = 'dia-row__hint';
            hint.textContent = 'Libre desde ' + efectivo;
            hint.hidden      = false;
        } else {
            hint.hidden = true;
        }
    });
}

// ── Docente: listener de cambio ───────────────────────────────

function initDocenteHint() {
    const docenteSel = document.getElementById('docente_id');
    if (!docenteSel) return;
    docenteSel.addEventListener('change', actualizarHints);
    // Inicializar si ya viene preseleccionado (editar / unidocente)
    actualizarHints();
}

// ── Disponibilidad de areas y subareas ────────────────────────

function getSubareasDeArea(areaId) {
    return Array.from(
        document.querySelectorAll(`#subarea_id option[data-area-id="${areaId}"]`)
    ).map(o => parseInt(o.value, 10)).filter(Boolean);
}

// ── Area / Subarea ────────────────────────────────────────────

function initAreaFilter() {
    const seccionSel = document.getElementById('seccion_id');
    const areaSel    = document.getElementById('area_id');
    if (!seccionSel || !areaSel) return;

    seccionSel.addEventListener('change', () => {
        filtrarAreas();
        autoSeleccionarTutor(seccionSel.options[seccionSel.selectedIndex]);
        // autoSeleccionarTutor puede cambiar docenteSel.value, por eso se llama despues
        actualizarHints();
    });
    areaSel.addEventListener('change', filtrarSubareas);

    // Inicializar (editar y crear-con-seccion: ya hay valores seleccionados por PHP)
    filtrarAreas();
}

function autoSeleccionarTutor(seccionOpt) {
    const docenteSel   = document.getElementById('docente_id');
    if (!docenteSel) return;
    const esUnidocente = seccionOpt?.dataset?.esUnidocente === '1';
    const tutorId      = seccionOpt?.dataset?.tutorId || '';
    if (esUnidocente && tutorId) {
        docenteSel.value = tutorId;
    }
}

function filtrarAreas() {
    const seccionSel  = document.getElementById('seccion_id');
    const areaSel     = document.getElementById('area_id');
    const selectedOpt = seccionSel.options[seccionSel.selectedIndex];

    const nivelId    = selectedOpt?.dataset?.nivelId;
    const seccionId  = selectedOpt?.value || '';
    const areaPresel = areaSel.dataset.selected; // solo en editar

    const usadas = (getDato('ocupadas')[seccionId] || { areas: [], subareas: [] });

    Array.from(areaSel.options).forEach(opt => {
        if (!opt.value) return;

        const mismoNivel = opt.dataset.nivelId === nivelId;
        if (!mismoNivel) { opt.style.display = 'none'; return; }

        let disponible;
        if (opt.dataset.tipo === 'con_subareas') {
            const subs = getSubareasDeArea(opt.value);
            disponible = subs.length === 0 ||
                         subs.some(id => !usadas.subareas.includes(id));
        } else {
            disponible = !usadas.areas.includes(parseInt(opt.value, 10));
        }

        opt.style.display = disponible ? '' : 'none';
    });

    areaSel.options[0].textContent = nivelId
        ? 'Seleccionar area...'
        : 'Selecciona primero una seccion...';

    areaSel.value = '';
    if (areaPresel) {
        const preOpt = areaSel.querySelector(`option[value="${areaPresel}"]`);
        if (preOpt && preOpt.dataset.nivelId === nivelId && preOpt.style.display !== 'none') {
            areaSel.value = areaPresel;
        }
    }

    filtrarSubareas();
}

function filtrarSubareas() {
    const areaSel          = document.getElementById('area_id');
    const subareaContainer = document.getElementById('subarea-container');
    const subareaSel       = document.getElementById('subarea_id');
    const seccionSel       = document.getElementById('seccion_id');

    if (!subareaContainer || !subareaSel) return;

    const selectedOpt = areaSel.options[areaSel.selectedIndex];
    const tipo        = selectedOpt?.dataset?.tipo;
    const esConSub    = tipo === 'con_subareas';
    const areaId      = areaSel.value;
    const subPresel   = subareaSel.dataset.selected; // solo en editar

    const seccionId = seccionSel?.value || '';
    const usadas    = (getDato('ocupadas')[seccionId] || { areas: [], subareas: [] });

    subareaContainer.style.display = esConSub ? 'flex' : 'none';
    subareaSel.disabled = !esConSub;

    let primera = true;
    Array.from(subareaSel.options).forEach(opt => {
        if (!opt.value) return;

        const mismaArea  = opt.dataset.areaId === areaId;
        const disponible = !usadas.subareas.includes(parseInt(opt.value, 10));
        const visible    = mismaArea && disponible;

        opt.style.display = visible ? '' : 'none';

        if (visible && subPresel && opt.value === subPresel && primera) {
            subareaSel.value = subPresel;
            primera = false;
        }
    });

    if (!esConSub) subareaSel.value = '';
}
