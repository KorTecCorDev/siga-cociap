/**
 * secciones.js — SIGA-COCIAP
 * Gestión de secciones y asignación de tutores.
 */

var BASE = document.querySelector('meta[name="base-url"]')
    ? document.querySelector('meta[name="base-url"]').content
    : '';

// ── Abrir modal de asignación de tutor ───────────────────────
function abrirModalTutor(btn) {
    var seccionId     = btn.getAttribute('data-seccion-id');
    var tutorActualId = btn.getAttribute('data-tutor-id') || '';
    var label         = btn.getAttribute('data-label')    || '';
    var nivel         = btn.getAttribute('data-nivel')    || '';

    if (tutorActualId === '0') tutorActualId = '';

    // Título dinámico
    document.querySelector('#modalTutor .modal-title').textContent =
        tutorActualId ? 'Cambiar tutor' : 'Asignar tutor';

    // Info de sección y nivel
    document.getElementById('modalSeccionLabel').textContent = label;
    var badge = document.getElementById('modalNivelBadge');
    badge.textContent = nivel;
    badge.className   = 'modal-nivel-badge modal-nivel-badge--' +
        (nivel.toLowerCase().indexOf('prim') !== -1 ? 'primaria' : 'secundaria');

    // Acción del formulario
    document.getElementById('formTutor').action =
        BASE + '/admin/secciones/' + seccionId + '/tutor';

    // Reconstruir select con solo los docentes disponibles
    var sel      = document.getElementById('tutor_id');
    var docentes = JSON.parse(
        document.getElementById('modalTutorData').dataset.docentes
    );

    sel.innerHTML = '<option value="">— Quitar tutor —</option>';

    docentes.forEach(function (d) {
        var esTutorActual  = String(d.id) === tutorActualId;
        var estaDisponible = d.seccionId === 0;

        // Mostrar solo disponibles y el tutor actual de esta sección
        if (!estaDisponible && !esTutorActual) return;
        // Inactivos: solo si son el tutor actual
        if (d.inactivo && !esTutorActual) return;

        var opt  = document.createElement('option');
        opt.value = d.id;
        var texto = d.nombre + ' (' + d.dni + ')';
        if (d.inactivo)    texto += ' — inactivo';
        if (esTutorActual) texto += ' (actual)';
        opt.textContent = texto;
        sel.appendChild(opt);
    });

    if (tutorActualId) sel.value = tutorActualId;

    _setFeedback('', '');
    var btnEnv = document.querySelector('#formTutor [type="submit"]');
    if (btnEnv) {
        btnEnv.disabled    = false;
        btnEnv.textContent = 'Guardar';
    }

    Modal.abrir('modalTutor');
}

// ── Envío del formulario vía AJAX ────────────────────────────
// Resuelve el problema de que el POST tradicional salía de BrowserSync:
// con AJAX la respuesta es JSON y recargamos con window.location.reload(),
// que preserva la URL actual (puerto 3000 u otro entorno).
document.getElementById('formTutor').addEventListener('submit', function (e) {
    e.preventDefault();

    var form   = this;
    var sel    = document.getElementById('tutor_id');
    var btnEnv = form.querySelector('[type="submit"]');

    // Confirmación solo al quitar el tutor
    if (sel.value === '') {
        if (!confirm('¿Confirma que desea quitar el tutor de esta sección?')) return;
    }

    btnEnv.disabled    = true;
    btnEnv.textContent = 'Guardando…';
    _setFeedback('Guardando...', 'loading');

    fetch(form.action, { method: 'POST', body: new FormData(form) })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                Modal.cerrar('modalTutor');
                window.location.reload();
            } else {
                _setFeedback(data.mensaje, 'error');
                btnEnv.disabled    = false;
                btnEnv.textContent = 'Guardar';
            }
        })
        .catch(function () {
            _setFeedback('Error de conexión. Intenta de nuevo.', 'error');
            btnEnv.disabled    = false;
            btnEnv.textContent = 'Guardar';
        });
});

// ── Helper de feedback en el modal ───────────────────────────
function _setFeedback(texto, tipo) {
    var el = document.getElementById('modalFeedback');
    if (!el) return;
    el.textContent = texto;
    el.className   = tipo ? 'modal-feedback modal-feedback--' + tipo : 'modal-feedback';
}
