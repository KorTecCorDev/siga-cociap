/**
 * cargas.js — Formulario de carga académica
 * Filtrado de áreas por nivel, subáreas dinámicas y toggle de días.
 */

document.addEventListener('DOMContentLoaded', () => {
    initDiasToggle();
    initAreaFilter();
});

// ── Días ─────────────────────────────────────────────────────

function initDiasToggle() {
    document.querySelectorAll('.dia-check').forEach(cb => {
        cb.addEventListener('change', () => toggleDia(cb));
        // editar: inicializar el estado visual del estado actual
        if (cb.checked) {
            document.getElementById('dia-row-' + cb.value)?.classList.add('dia-row--activo');
        }
    });
}

function toggleDia(cb) {
    const row    = document.getElementById('dia-row-' + cb.value);
    const inputs = row.querySelectorAll('input[type="time"]');

    inputs.forEach(input => {
        input.disabled = !cb.checked;
        if (!cb.checked) input.value = '';
    });
    row.classList.toggle('dia-row--activo', cb.checked);
}

// ── Área / Subárea ────────────────────────────────────────────

function initAreaFilter() {
    const seccionSel = document.getElementById('seccion_id');
    const areaSel    = document.getElementById('area_id');

    if (!seccionSel || !areaSel) return;

    seccionSel.addEventListener('change', filtrarAreas);
    areaSel.addEventListener('change', filtrarSubareas);

    // Inicializar (editar: ya hay valores seleccionados)
    filtrarAreas();
}

function filtrarAreas() {
    const seccionSel = document.getElementById('seccion_id');
    const areaSel    = document.getElementById('area_id');
    const selectedOpt = seccionSel.options[seccionSel.selectedIndex];

    const nivelId     = selectedOpt?.dataset?.nivelId;
    const areaPresel  = areaSel.dataset.selected; // solo en editar

    // Mostrar / ocultar opciones según nivel
    let hayOpciones = false;
    Array.from(areaSel.options).forEach(opt => {
        if (!opt.value) return; // placeholder
        const visible = opt.dataset.nivelId === nivelId;
        opt.style.display = visible ? '' : 'none';
        if (visible) hayOpciones = true;
    });

    // Limpiar placeholder
    areaSel.options[0].textContent = nivelId
        ? 'Seleccionar área...'
        : 'Selecciona primero una sección...';

    // En editar, reseleccionar el área guardada si coincide con el nivel
    if (areaPresel) {
        const preOpt = areaSel.querySelector(`option[value="${areaPresel}"]`);
        if (preOpt && preOpt.dataset.nivelId === nivelId) {
            areaSel.value = areaPresel;
        } else if (areaSel.value !== areaPresel) {
            areaSel.value = '';
        }
    }

    filtrarSubareas();
}

function filtrarSubareas() {
    const areaSel        = document.getElementById('area_id');
    const subareaContainer = document.getElementById('subarea-container');
    const subareaSel     = document.getElementById('subarea_id');

    if (!subareaContainer || !subareaSel) return;

    const selectedOpt = areaSel.options[areaSel.selectedIndex];
    const tipo        = selectedOpt?.dataset?.tipo;
    const esConSub    = tipo === 'con_subareas';
    const areaId      = areaSel.value;
    const subPresel   = subareaSel.dataset.selected; // solo en editar

    subareaContainer.style.display = esConSub ? 'flex' : 'none';
    subareaSel.disabled = !esConSub;

    // Mostrar solo las subáreas del área seleccionada
    let primera = true;
    Array.from(subareaSel.options).forEach(opt => {
        if (!opt.value) return;
        const visible = opt.dataset.areaId === areaId;
        opt.style.display = visible ? '' : 'none';

        // Reseleccionar en editar
        if (visible && subPresel && opt.value === subPresel && primera) {
            subareaSel.value = subPresel;
            primera = false;
        }
    });

    if (!esConSub) subareaSel.value = '';
}
