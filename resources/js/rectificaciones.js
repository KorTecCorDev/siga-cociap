/**
 * rectificaciones.js — SIGA-COCIAP
 * Vista /rectificaciones/editar: muestra en vivo la evolución del promedio
 * de la competencia (numeral + literal) a medida que se editan las notas por
 * criterio. El cálculo replica al backend: promedio = ROUND(AVG(notas)) sobre
 * los criterios CON nota (vacío = NULL, no cuenta), y el literal sale de los
 * mismos umbrales que app/Helpers/helpers.php (AD: 18-20 · A: 14-17 ·
 * B: 11-13 · C: 00-10). PUNTO ÚNICO DE VERDAD en PHP: si cambian allí, aquí.
 */
(function () {
    var NOTA_MIN_AD = 18;
    var NOTA_MIN_A  = 14;
    var NOTA_MIN_B  = 11;

    var preview = document.getElementById('rectPreview');
    if (!preview) return;

    var inputs  = Array.prototype.slice.call(document.querySelectorAll('.rect-nota-input'));
    if (inputs.length === 0) return;

    var elNum = preview.querySelector('[data-rect-num]');
    var elLit = preview.querySelector('[data-rect-lit]');

    function literal(n) {
        if (n >= NOTA_MIN_AD) return 'AD';
        if (n >= NOTA_MIN_A)  return 'A';
        if (n >= NOTA_MIN_B)  return 'B';
        return 'C';
    }

    function dosDigitos(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function recalcular() {
        var suma = 0;
        var cuenta = 0;

        inputs.forEach(function (input) {
            var bruto = input.value.trim();
            if (bruto === '') return;             // vacío = NULL, no promedia
            var n = parseInt(bruto, 10);
            if (isNaN(n)) return;
            if (n < 0)  n = 0;
            if (n > 20) n = 20;                    // clamp igual que el backend
            suma += n;
            cuenta += 1;
        });

        // Sin ninguna nota: no hay promedio que mostrar.
        if (cuenta === 0) {
            elNum.textContent = '—';
            elLit.textContent = '—';
            preview.dataset.rectLiteral = '';
            return;
        }

        var prom = Math.round(suma / cuenta);      // ROUND(AVG(...)) del backend
        var lit  = literal(prom);

        elNum.textContent = dosDigitos(prom);
        elLit.textContent = lit;
        preview.dataset.rectLiteral = lit;         // permite colorear por literal
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', recalcular);
    });

    recalcular(); // estado inicial al cargar
})();
