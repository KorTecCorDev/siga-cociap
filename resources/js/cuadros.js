/**
 * Gráficos del tablero de Dirección (/admin/cuadros y su imprimible).
 * Lee los datos desde el <script type="application/json" id="cuadros-data">
 * y los dibuja con Frappe Charts (vendorizado en js/frappe-charts.min.js).
 *
 * DOS PALETAS, y la distinción importa:
 *
 *  · Estados (tienen orden y significado): verde #16a34a, ámbar #d97706,
 *    rojo #dc2626. Se usan en el embudo de conducta, que es un progreso.
 *    Los literales AD/A/B/C también son estados — su donut lo pinta el
 *    partial _panel-bimestre.php con las variables --lit-* del SASS.
 *
 *  · Categorías (no tienen orden): azul #1e6fa8, teal #0d9488,
 *    púrpura #7c3aed, naranja #e07b1a. Púrpura es el color de conducta en
 *    el wayfinding del sistema (resources/sass/base/_variables.scss).
 *
 * Por eso aquí SÍ aparecen rojo y ámbar, pese a la regla de _variables.scss
 * que los reserva: allí se prohíben como identidad de categoría, y estos
 * son estados. No "corregir" a azul/teal sin leer esto.
 */
(function () {
    'use strict';

    if (typeof frappe === 'undefined' || !frappe.Chart) {
        return;
    }

    var dataEl = document.getElementById('cuadros-data');
    if (!dataEl) {
        return;
    }

    var data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch (e) {
        return;
    }

    var AZUL    = '#1e6fa8';
    var TEAL    = '#0d9488';
    var PURPURA = '#7c3aed';
    var NARANJA = '#e07b1a';

    var VERDE = '#16a34a';
    var AMBAR = '#d97706';
    var ROJO  = '#dc2626';

    // Línea: evolución del % en logro por bimestre, una serie por nivel.
    // El PHP ya entregó las series recortadas a los bimestres con datos, así
    // que aquí no hay huecos que tratar.
    if (data.evolucion && data.evolucion.labels && data.evolucion.labels.length) {
        new frappe.Chart('#chart-evolucion', {
            type: 'line',
            height: 300,
            colors: [AZUL, TEAL],
            axisOptions: { xAxisMode: 'tick' },
            lineOptions: { hideDots: 0, regionFill: 0 },
            tooltipOptions: {
                formatTooltipY: function (d) { return d + '% en logro'; }
            },
            data: {
                labels: data.evolucion.labels,
                datasets: data.evolucion.datasets
            }
        });
    }

    // Barras agrupadas: primer puesto vs último puesto de cada grado.
    if (data.brecha && data.brecha.labels && data.brecha.labels.length) {
        new frappe.Chart('#chart-brecha', {
            type: 'bar',
            height: 320,
            colors: [AZUL, NARANJA],
            axisOptions: { xAxisMode: 'tick' },
            barOptions: { spaceRatio: 0.4 },
            data: {
                labels: data.brecha.labels,
                datasets: [
                    { name: 'Primer puesto', values: data.brecha.mejor },
                    { name: 'Último puesto', values: data.brecha.peor }
                ]
            }
        });
    }

    // Torta: en qué etapa del cierre está cada sección.
    if (data.conductaEmbudo && data.conductaEmbudo.values && data.conductaEmbudo.values.length) {
        new frappe.Chart('#chart-conducta-embudo', {
            type: 'pie',
            height: 280,
            colors: [VERDE, AMBAR, ROJO],
            data: {
                labels: data.conductaEmbudo.labels,
                datasets: [{ values: data.conductaEmbudo.values }]
            }
        });
    }

    // Barras: secciones con menor cobertura de conducta.
    if (data.conductaSecciones && data.conductaSecciones.labels && data.conductaSecciones.labels.length) {
        new frappe.Chart('#chart-conducta-secciones', {
            type: 'bar',
            height: 280,
            colors: [PURPURA],
            axisOptions: { xAxisMode: 'tick' },
            barOptions: { spaceRatio: 0.35 },
            tooltipOptions: {
                formatTooltipY: function (d) { return d + '% calificado'; }
            },
            data: {
                labels: data.conductaSecciones.labels,
                datasets: [{ name: 'Calificados', values: data.conductaSecciones.values }]
            }
        });
    }
})();
