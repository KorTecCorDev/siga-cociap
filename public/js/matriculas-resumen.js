/**
 * Dashboard de matrículas (/matriculas/resumen).
 * Lee los datos desde el <script type="application/json" id="resumen-data">
 * y dibuja los gráficos con Frappe Charts (vendorizado en js/frappe-charts.min.js).
 *
 * Colores alineados al wayfinding del sistema (resources/sass/base/_variables.scss):
 * azul #1e6fa8 (académicas), teal #0d9488 (transversales), púrpura #7c3aed (conducta).
 * Rojo/ámbar quedan reservados para estados, NUNCA como identidad de categoría.
 */
(function () {
    'use strict';

    if (typeof frappe === 'undefined' || !frappe.Chart) {
        return;
    }

    var dataEl = document.getElementById('resumen-data');
    if (!dataEl) {
        return;
    }

    var data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch (e) {
        return;
    }

    var AZUL = '#1e6fa8';

    // Barras: matriculados por grado.
    if (data.porGrado && data.porGrado.values.length) {
        new frappe.Chart('#chart-grado', {
            type: 'bar',
            height: 300,
            colors: [AZUL],
            axisOptions: { xAxisMode: 'tick' },
            barOptions: { spaceRatio: 0.4 },
            data: {
                labels: data.porGrado.labels,
                datasets: [{ name: 'Matriculados', values: data.porGrado.values }]
            }
        });
    }

    // Barras: matriculados por sección.
    if (data.porSeccion && data.porSeccion.values.length) {
        new frappe.Chart('#chart-seccion', {
            type: 'bar',
            height: 320,
            colors: [AZUL],
            axisOptions: { xAxisMode: 'tick' },
            barOptions: { spaceRatio: 0.35 },
            data: {
                labels: data.porSeccion.labels,
                datasets: [{ name: 'Matriculados', values: data.porSeccion.values }]
            }
        });
    }

    // Barras apiladas: varones / mujeres / sin dato por sección.
    if (data.generoSeccion && data.generoSeccion.labels.length) {
        new frappe.Chart('#chart-genero-seccion', {
            type: 'bar',
            height: 340,
            colors: [AZUL, '#0d9488', '#9ca3af'],
            axisOptions: { xAxisMode: 'tick' },
            barOptions: { spaceRatio: 0.35, stacked: 1 },
            data: {
                labels: data.generoSeccion.labels,
                datasets: [
                    { name: 'Masculino', values: data.generoSeccion.m },
                    { name: 'Femenino',  values: data.generoSeccion.f },
                    { name: 'Sin dato',  values: data.generoSeccion.sinDato }
                ]
            }
        });
    }

    // Pie: por tipo de matrícula.
    if (data.porTipo && data.porTipo.values.length) {
        new frappe.Chart('#chart-tipo', {
            type: 'pie',
            height: 280,
            colors: data.porTipo.colors,
            data: {
                labels: data.porTipo.labels,
                datasets: [{ values: data.porTipo.values }]
            }
        });
    }

    // Pie: por género (incluye "Sin dato").
    if (data.genero && data.genero.values.length) {
        new frappe.Chart('#chart-genero', {
            type: 'pie',
            height: 280,
            colors: data.genero.colors,
            data: {
                labels: data.genero.labels,
                datasets: [{ values: data.genero.values }]
            }
        });
    }
})();
