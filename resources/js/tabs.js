/**
 * tabs.js — pestañas dentro de una pantalla (componente global).
 *
 * Estilo en `resources/sass/components/_tabs.scss`; ahí está el porqué de que
 * exista y cuándo usarlo en vez de un conmutador de enlaces.
 *
 * MARCADO ESPERADO — el servidor decide la pestaña inicial, y sin JavaScript
 * la página sigue siendo correcta (se ve el panel que el servidor dejó
 * visible, simplemente no se puede cambiar):
 *
 *   <div class="tabs" role="tablist" data-tabs="conducta"
 *        data-tabs-memoria="cuadros.tab.conducta.2">
 *     <button class="tab tab--activa" role="tab" id="tab-x"
 *             data-tab="x" aria-controls="panel-x" aria-selected="true">X</button>
 *     ...
 *   </div>
 *   <div id="panel-x" class="tab-panel" role="tabpanel"
 *        data-panel="x" aria-labelledby="tab-x">...</div>
 *
 * SIEMPRE hay una pestaña activa. Es la diferencia deliberada con el hub de
 * `/director/bloqueos`, que nace colapsado y cuyo segundo clic cierra el
 * panel: allí el detalle es opcional y caro; aquí un grupo sin nada visible
 * sería una sección en blanco.
 *
 * `data-tabs-memoria` es OPCIONAL. Si está, se recuerda la última pestaña en
 * `localStorage` con esa clave. La clave la elige quien renderiza porque solo
 * él sabe de qué depende: en el tablero de Dirección lleva el id del bimestre,
 * para que la pestaña recordada no se mezcle entre bimestres distintos.
 *
 * Al mostrar un panel se emite `tabs:mostrado` sobre el propio panel (burbujea),
 * con `detail = { grupo, nombre }`. Es el único acoplamiento hacia fuera, y
 * existe por un motivo concreto: un gráfico SVG instanciado dentro de un
 * contenedor oculto se mide a 0 px y nace roto, así que quien dibuje gráficos
 * necesita enterarse de que su panel acaba de hacerse visible. Este archivo no
 * sabe nada de gráficos y no debe saberlo.
 */
(function () {
    'use strict';

    var grupos = document.querySelectorAll('[data-tabs]');
    if (!grupos.length) {
        return;
    }

    Array.prototype.forEach.call(grupos, function (lista) {
        var grupo   = lista.getAttribute('data-tabs');
        var memoria = lista.getAttribute('data-tabs-memoria');
        var tabs    = Array.prototype.slice.call(lista.querySelectorAll('[data-tab]'));
        if (!tabs.length) {
            return;
        }

        // Los paneles se buscan en el documento, no dentro de la lista: van
        // como hermanos de la tira, no anidados en ella.
        var paneles = Array.prototype.slice.call(
            document.querySelectorAll('[data-panel]')
        ).filter(function (p) {
            return tabs.some(function (t) { return t.getAttribute('data-tab') === p.getAttribute('data-panel'); });
        });

        // localStorage puede lanzar (modo privado, cookies bloqueadas). Que la
        // memoria falle no puede dejar las pestañas sin funcionar.
        function recordar(nombre) {
            if (!memoria) { return; }
            try { window.localStorage.setItem(memoria, nombre); } catch (e) { /* sin memoria */ }
        }

        function recordado() {
            if (!memoria) { return null; }
            try { return window.localStorage.getItem(memoria); } catch (e) { return null; }
        }

        function mostrar(nombre, moverFoco) {
            tabs.forEach(function (t) {
                var activa = t.getAttribute('data-tab') === nombre;
                t.classList.toggle('tab--activa', activa);
                t.setAttribute('aria-selected', activa ? 'true' : 'false');
                // Roving tabindex: el Tab del teclado entra y sale del grupo de
                // una vez, y las flechas se mueven dentro. Recorrer siete
                // pestañas a base de Tab para llegar al contenido es hostil.
                t.setAttribute('tabindex', activa ? '0' : '-1');
                if (activa && moverFoco) { t.focus(); }
            });

            paneles.forEach(function (p) {
                var activo = p.getAttribute('data-panel') === nombre;
                p.hidden = !activo;
                if (activo) {
                    p.dispatchEvent(new CustomEvent('tabs:mostrado', {
                        bubbles: true,
                        detail: { grupo: grupo, nombre: nombre }
                    }));
                }
            });

            recordar(nombre);
        }

        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () {
                mostrar(t.getAttribute('data-tab'), false);
            });

            t.addEventListener('keydown', function (ev) {
                var destino = null;
                if (ev.key === 'ArrowRight') { destino = (i + 1) % tabs.length; }
                else if (ev.key === 'ArrowLeft') { destino = (i - 1 + tabs.length) % tabs.length; }
                else if (ev.key === 'Home') { destino = 0; }
                else if (ev.key === 'End') { destino = tabs.length - 1; }
                if (destino === null) { return; }

                ev.preventDefault();
                mostrar(tabs[destino].getAttribute('data-tab'), true);
            });
        });

        // Estado inicial: lo recordado si esa pestaña sigue existiendo (un
        // bimestre puede no tener los mismos bloques que otro), y si no, la que
        // el servidor marcó como activa.
        var guardado = recordado();
        var inicial  = tabs.filter(function (t) { return t.getAttribute('data-tab') === guardado; })[0]
            || tabs.filter(function (t) { return t.classList.contains('tab--activa'); })[0]
            || tabs[0];

        mostrar(inicial.getAttribute('data-tab'), false);
    });
})();
