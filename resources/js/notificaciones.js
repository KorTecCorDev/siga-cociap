/**
 * notificaciones.js — SIGA-COCIAP
 * Bandeja /notificaciones: marcar como leída sin recargar la página.
 *
 * El servidor devuelve el contador actualizado, así que la campana de la barra
 * se sincroniza con la misma respuesta: no hay dos fuentes de verdad.
 */
(function () {
    var lista = document.querySelector('.notif-lista');
    if (!lista) return;

    var meta  = document.querySelector('meta[name="csrf-token"]');
    var base  = document.querySelector('meta[name="base-url"]');
    if (!meta || !base) return;

    var token   = meta.getAttribute('content');
    var urlLeer = base.getAttribute('content') + '/notificaciones/leer';

    /** Refleja en la campana el contador que acaba de devolver el servidor. */
    function pintarCampana(noLeidas) {
        var campana = document.querySelector('.navbar__notif');
        if (!campana) return;

        var badge = campana.querySelector('.navbar__notif-badge');
        if (noLeidas > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'navbar__notif-badge';
                campana.appendChild(badge);
            }
            badge.textContent = noLeidas > 99 ? '99+' : String(noLeidas);
            campana.classList.add('navbar__notif--pendientes');
        } else {
            if (badge) badge.remove();
            campana.classList.remove('navbar__notif--pendientes');
        }
    }

    function marcarLeida(item, boton) {
        var id = item.dataset.notifId;
        if (!id) return;

        var datos = new FormData();
        datos.append('id', id);
        datos.append('_csrf_token', token);

        fetch(urlLeer, {
            method: 'POST',
            body: datos,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || res.success !== true) return;
                item.classList.remove('notif--sin-leer');
                var punto = item.querySelector('.notif__punto');
                if (punto) punto.remove();
                if (boton) boton.remove();
                pintarCampana(res.noLeidas || 0);
            })
            .catch(function () { /* silencioso: marcar leída no es crítico */ });
    }

    lista.addEventListener('click', function (ev) {
        var item = ev.target.closest('.notif');
        if (!item) return;

        // Botón explícito "Marcar como leída".
        var boton = ev.target.closest('[data-notif-leer]');
        if (boton) {
            marcarLeida(item, boton);
            return;
        }

        // Abrir el detalle también la da por leída (el enlace sigue su curso).
        if (ev.target.closest('[data-notif-abrir]') && item.classList.contains('notif--sin-leer')) {
            marcarLeida(item, item.querySelector('[data-notif-leer]'));
        }
    });
})();
