/**
 * rectificaciones-lote.js — SIGA-COCIAP
 * Grilla de CALIFICACIÓN EXTRAORDINARIA EN LOTE (/rectificaciones/extraordinaria/lote).
 *
 * Hace tres cosas, todas de captura; ninguna regla de negocio vive aquí:
 *   1. Muestra el literal en vivo de cada nota tecleada.
 *   2. Revela la conclusión descriptiva SOLO en las filas cuyo literal la
 *      exige, y le pone `required` únicamente cuando está visible (un campo
 *      required oculto bloquea el envío sin decir por qué).
 *   3. Cuenta las filas con nota y habilita el botón de registrar.
 *
 * ⚠️ Los umbrales de la escala y los literales que exigen conclusión NO se
 * escriben aquí: llegan en data-* desde PHP, que los saca de las constantes de
 * app/Helpers/helpers.php y de CalificacionModel::conclusionObligatoria. El
 * PUNTO ÚNICO DE VERDAD sigue estando en PHP.
 */
(function () {
    var form = document.getElementById('rectLoteForm');
    if (!form) return;

    var NOTA_MIN_AD = parseInt(form.dataset.notaMinAd, 10);
    var NOTA_MIN_A  = parseInt(form.dataset.notaMinA, 10);
    var NOTA_MIN_B  = parseInt(form.dataset.notaMinB, 10);
    if (isNaN(NOTA_MIN_AD) || isNaN(NOTA_MIN_A) || isNaN(NOTA_MIN_B)) return;

    var exigenConclusion = (form.dataset.literalesConclusion || '')
        .split(',')
        .filter(function (l) { return l !== ''; });

    var inputs   = Array.prototype.slice.call(form.querySelectorAll('.rect-lote__nota'));
    var elLlenas = form.querySelector('[data-rect-lote-llenas]');
    var btn      = form.querySelector('[data-rect-lote-submit]');
    if (inputs.length === 0) return;

    function literal(n) {
        if (n >= NOTA_MIN_AD) return 'AD';
        if (n >= NOTA_MIN_A)  return 'A';
        if (n >= NOTA_MIN_B)  return 'B';
        return 'C';
    }

    /** Nota válida tecleada, o null si la fila está vacía o no es un número. */
    function notaDe(input) {
        var bruto = input.value.trim();
        if (bruto === '') return null;
        var n = parseInt(bruto, 10);
        if (isNaN(n)) return null;
        if (n < 0)  n = 0;
        if (n > 20) n = 20;
        return n;
    }

    function filaDe(input) {
        return input.closest('tr');
    }

    function actualizarFila(input) {
        var fila  = filaDe(input);
        if (!fila) return;
        var clave = fila.dataset.clave;
        var celda = fila.querySelector('.rect-lote__literal');
        var bloqueConclusion = form.querySelector(
            '.rect-lote__conclusion[data-clave="' + clave + '"]'
        );
        var textarea = bloqueConclusion
            ? bloqueConclusion.querySelector('textarea')
            : null;

        var n = notaDe(input);

        if (n === null) {
            if (celda) {
                celda.textContent = '—';
                celda.dataset.literal = '';
            }
            if (bloqueConclusion) {
                bloqueConclusion.hidden = true;
                if (textarea) textarea.required = false;
            }
            return;
        }

        var lit = literal(n);
        if (celda) {
            celda.textContent = lit;
            celda.dataset.literal = lit;
        }

        // La conclusión se pide SOLO donde la regla del nivel la exige, y
        // `required` se pone únicamente con el campo a la vista.
        var hace = exigenConclusion.indexOf(lit) !== -1;
        if (bloqueConclusion) {
            bloqueConclusion.hidden = !hace;
            if (textarea) textarea.required = hace;
        }
    }

    function actualizarContador() {
        var llenas = 0;
        inputs.forEach(function (input) {
            if (notaDe(input) !== null) llenas += 1;
        });
        if (elLlenas) elLlenas.textContent = String(llenas);
        if (btn) btn.disabled = llenas === 0;
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            actualizarFila(input);
            actualizarContador();
        });
        // Estado inicial (por si el navegador restaura valores al volver atrás).
        actualizarFila(input);
    });

    actualizarContador();
})();
