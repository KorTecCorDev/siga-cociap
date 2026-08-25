/**
 * Explorador de criterios — expandir / contraer todo el arbol.
 *
 * El arbol es <details> nativo: se abre y cierra sin JavaScript, y la apertura
 * inicial la decide el servidor (regla anti-avalancha). Esto solo anade los dos
 * botones globales, asi que si el script no carga la pantalla sigue siendo
 * plenamente usable.
 */
(function () {
    'use strict';

    var arbol = document.getElementById('criterios-arbol');
    if (!arbol) { return; }

    document.querySelectorAll('[data-arbol]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var abrir = boton.dataset.arbol === 'expandir';
            arbol.querySelectorAll('details').forEach(function (d) {
                d.open = abrir;
            });
        });
    });
}());
