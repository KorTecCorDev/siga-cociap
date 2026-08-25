/**
 * Explorador de criterios — arbol (expandir/contraer) y cascada de filtros.
 *
 * El arbol es <details> nativo: se abre y cierra sin JavaScript, y la apertura
 * inicial la decide el servidor (regla anti-avalancha). Los dos botones globales
 * son un extra, asi que si el script no carga la pantalla sigue siendo usable.
 *
 * La CASCADA de filtros tambien degrada bien: sin JS los cuatro selectores salen
 * completos y el formulario sigue enviando; lo que se pierde es el recorte, no
 * la funcion. El filtrado de verdad lo hace el servidor en cualquier caso.
 */
(function () {
    'use strict';

    // ── Arbol ────────────────────────────────────────────────────────────
    var arbol = document.getElementById('criterios-arbol');
    if (arbol) {
        document.querySelectorAll('[data-arbol]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var abrir = boton.dataset.arbol === 'expandir';
                arbol.querySelectorAll('details').forEach(function (d) {
                    d.open = abrir;
                });
            });
        });
    }

    // ── Cascada de filtros ───────────────────────────────────────────────
    // ⚠️ Va FUERA del guard del arbol: el formulario existe tambien cuando no
    // hay resultados, y es justo ahi donde el director necesita rectificar la
    // combinacion. Atarla al arbol la apagaria en la pantalla vacia.
    var selNivel   = document.getElementById('nivel');
    var selGrado   = document.getElementById('grado');
    var selSeccion = document.getElementById('seccion');
    var selDocente = document.getElementById('docente');
    if (!selNivel || !selGrado || !selSeccion || !selDocente) { return; }

    // Mapa seccion_id -> { nivel, grado }, leido de las propias <option> de
    // Seccion: el nivel y el grado de una seccion ya viajan ahi, asi que no
    // hace falta un segundo bloque de datos que pueda desincronizarse.
    var infoSeccion = {};
    Array.prototype.forEach.call(selSeccion.options, function (o) {
        if (o.value === '0') { return; }
        infoSeccion[o.value] = {
            nivel: parseInt(o.dataset.nivelId, 10),
            grado: parseInt(o.dataset.gradoId, 10)
        };
    });

    /** ¿Esta seccion sobrevive a los filtros de nivel / grado / seccion? */
    function seccionEncaja(sid, nivel, grado, seccion) {
        var info = infoSeccion[sid];
        if (!info) { return false; }
        if (seccion && parseInt(sid, 10) !== seccion) { return false; }
        if (nivel   && info.nivel !== nivel) { return false; }
        if (grado   && info.grado !== grado) { return false; }
        return true;
    }

    /**
     * Oculta las opciones que no encajan y devuelve el valor VIGENTE.
     *
     * Devuelve el valor y no void a proposito: si lo que estaba elegido queda
     * oculto vuelve a "Todos" —en silencio, por decision de diseno— y los
     * eslabones siguientes tienen que encadenarse sobre ese valor nuevo, no
     * sobre el que acaba de invalidarse.
     *
     * Usa `hidden` y no `style.display`: ocultar una <option> por CSS no es
     * fiable en todos los navegadores.
     */
    function recortar(sel, encaja) {
        Array.prototype.forEach.call(sel.options, function (o) {
            if (o.value === '0') { return; }   // "Todos" nunca se oculta
            o.hidden = !encaja(o);
        });
        var elegida = sel.options[sel.selectedIndex];
        if (elegida && elegida.hidden) { sel.value = '0'; }
        return parseInt(sel.value, 10) || 0;
    }

    function aplicarCascada() {
        var nivel = parseInt(selNivel.value, 10) || 0;

        var grado = recortar(selGrado, function (o) {
            return !nivel || parseInt(o.dataset.nivelId, 10) === nivel;
        });

        var seccion = recortar(selSeccion, function (o) {
            return (!nivel || parseInt(o.dataset.nivelId, 10) === nivel)
                && (!grado || parseInt(o.dataset.gradoId, 10) === grado);
        });

        // El docente se recorta POR PERTENENCIA: aparece si al menos una de sus
        // secciones sobrevive. No se le puede asignar "su" nivel — hay docentes
        // que dictan en primaria Y secundaria, y perderian medio horario.
        recortar(selDocente, function (o) {
            var sids = (o.dataset.secciones || '').split(',');
            return sids.some(function (sid) {
                return seccionEncaja(sid, nivel, grado, seccion);
            });
        });
    }

    // Solo los tres de arriba disparan: la cascada es descendente
    // (nivel -> grado -> seccion -> docente). Elegir docente no recorta nada.
    selNivel.addEventListener('change', aplicarCascada);
    selGrado.addEventListener('change', aplicarCascada);
    selSeccion.addEventListener('change', aplicarCascada);

    // Al cargar: la pagina llega con los filtros de la URL ya aplicados y los
    // selectores completos, asi que hay que recortarlos de entrada.
    aplicarCascada();
}());
