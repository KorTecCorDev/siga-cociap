/**
 * notas-externas.js — SIGA-COCIAP
 * Formulario de NOTAS DEL COLEGIO DE ORIGEN (/matriculas/{id}/notas-externas).
 *
 * Única función: añadir filas al lote. El informe de origen puede traer 25+
 * competencias y el formulario nace con 6, así que se clona la última fila
 * vacía tantas veces como haga falta.
 *
 * El servidor descarta las filas en blanco, así que sobrar filas es inofensivo.
 */
(function () {
    var form = document.getElementById('notasOrigenForm');
    if (!form) return;

    var cuerpo = form.querySelector('[data-notas-origen-cuerpo]');
    var boton  = form.querySelector('[data-notas-origen-agregar]');
    if (!cuerpo || !boton) return;

    boton.addEventListener('click', function () {
        var filas = cuerpo.querySelectorAll('.notas-origen__fila');
        if (filas.length === 0) return;

        var nueva = filas[filas.length - 1].cloneNode(true);

        // Un clon arrastra los valores tecleados: se limpian para que la fila
        // nueva nazca vacía (los <select> vuelven a su primera opción).
        nueva.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        nueva.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });

        cuerpo.appendChild(nueva);

        var primer = nueva.querySelector('input');
        if (primer) primer.focus();
    });
})();
