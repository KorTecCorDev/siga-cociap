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

    // PHP escribe "0" cuando no hay tutor; en JS "0" es truthy
    if (tutorActualId === '0') tutorActualId = '';

    // Título dinámico según si hay tutor o no
    document.querySelector('#modalTutor .modal-title').textContent =
        tutorActualId ? 'Cambiar tutor' : 'Asignar tutor';

    // Info de sección y nivel
    document.getElementById('modalSeccionLabel').textContent = label;
    var badge = document.getElementById('modalNivelBadge');
    badge.textContent = nivel;
    badge.className   = 'modal-nivel-badge modal-nivel-badge--' +
        (nivel.toLowerCase().indexOf('prim') !== -1 ? 'primaria' : 'secundaria');

    // Acción dinámica del formulario
    document.getElementById('formTutor').action =
        BASE + '/admin/secciones/' + seccionId + '/tutor';

    // Pre-seleccionar tutor actual y marcar "(actual)"
    var sel = document.getElementById('tutor_id');
    for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].text = sel.options[i].text.replace(/\s*\(actual\)$/, '');
    }
    sel.value = tutorActualId;
    if (tutorActualId) {
        for (var j = 0; j < sel.options.length; j++) {
            if (sel.options[j].value === tutorActualId) {
                sel.options[j].text += ' (actual)';
                break;
            }
        }
    }

    // Limpiar feedback de la apertura anterior
    _setFeedback('', '');

    // Restaurar botón por si quedó deshabilitado
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
