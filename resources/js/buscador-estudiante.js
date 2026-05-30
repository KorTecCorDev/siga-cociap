/**
 * buscador-estudiante.js — SIGA-COCIAP
 * Búsqueda en vivo de estudiantes por DNI o apellidos/nombres.
 * Consulta el año académico activo vía endpoint JSON.
 */

(function () {
    var BASE = document.querySelector('meta[name="base-url"]')
        ? document.querySelector('meta[name="base-url"]').content
        : '';

    var input      = document.getElementById('buscadorInput');
    var resultados = document.getElementById('buscadorResultados');
    var spinner    = document.getElementById('buscadorSpinner');
    var limpiar    = document.getElementById('buscadorLimpiar');

    if (!input || !resultados) return;

    var MIN_CARACTERES = 2;
    var timer = null;
    var peticionActual = 0;

    // Etiquetas y clases por estado de matrícula
    var ESTADOS = {
        aprobada:              { texto: 'Aprobada',    clase: 'aprobada'  },
        registrada:            { texto: 'Registrada',  clase: 'pendiente' },
        pendiente_documentos:  { texto: 'Pend. docs.', clase: 'pendiente' },
        observada:             { texto: 'Observada',   clase: 'observada' },
        retirada:              { texto: 'Retirada',    clase: 'retirada'  }
    };

    input.addEventListener('input', function () {
        var q = input.value.trim();
        if (timer) clearTimeout(timer);

        toggleLimpiar();

        if (q.length < MIN_CARACTERES) {
            mostrarVacio();
            ocultarSpinner();
            return;
        }

        timer = setTimeout(function () { consultar(q); }, 250);
    });

    if (limpiar) {
        limpiar.addEventListener('click', function () {
            if (timer) clearTimeout(timer);
            peticionActual++;          // descarta cualquier respuesta en vuelo
            input.value = '';
            ocultarSpinner();
            toggleLimpiar();
            mostrarVacio();
            input.focus();
        });
    }

    // Permite limpiar con la tecla Escape
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && input.value !== '') {
            e.preventDefault();
            if (limpiar) limpiar.click();
        }
    });

    function consultar(q) {
        var idPeticion = ++peticionActual;
        mostrarSpinner();

        fetch(BASE + '/admin/buscar-estudiante/api?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                // Ignorar respuestas viejas si el usuario siguió escribiendo
                if (idPeticion !== peticionActual) return;
                ocultarSpinner();

                if (!data.success) {
                    mostrarMensaje(data.mensaje || 'No se pudo realizar la búsqueda.', 'error');
                    return;
                }
                renderizar(data.resultados || [], data.bimestre || null, !!data.tieneOrdenMerito);
            })
            .catch(function () {
                if (idPeticion !== peticionActual) return;
                ocultarSpinner();
                mostrarMensaje('Error de conexión. Intente de nuevo.', 'error');
            });
    }

    function renderizar(filas, bimestre, tieneOrden) {
        resultados.innerHTML = '';

        if (filas.length === 0) {
            mostrarMensaje('Alumno no matriculado en el año académico.', 'info');
            return;
        }

        var contador = document.createElement('p');
        contador.className = 'buscador-resultados__contador text-sm text-muted';
        contador.textContent = filas.length === 1
            ? '1 estudiante encontrado'
            : filas.length + ' estudiantes encontrados';
        resultados.appendChild(contador);

        // Nota global: a qué bimestre cerrado corresponden los puestos mostrados.
        // En el I Bimestre (sin ningún bimestre cerrado) no hay orden vigente.
        var nota = document.createElement('p');
        nota.className = 'buscador-resultados__bimestre text-sm text-muted';
        nota.textContent = (tieneOrden && bimestre)
            ? 'Orden de mérito vigente: ' + bimestre
            : 'No hay orden de mérito vigente';
        resultados.appendChild(nota);

        filas.forEach(function (f) {
            resultados.appendChild(crearTarjeta(f, tieneOrden));
        });
    }

    function crearTarjeta(f, tieneOrden) {
        var card = document.createElement('div');
        card.className = 'buscador-item card';

        var body = document.createElement('div');
        body.className = 'buscador-item__body';

        // Avatar con inicial
        var avatar = document.createElement('div');
        avatar.className = 'buscador-item__avatar';
        avatar.textContent = (f.nombre || '?').charAt(0).toUpperCase();
        body.appendChild(avatar);

        // Datos del estudiante
        var info = document.createElement('div');
        info.className = 'buscador-item__info';

        var nombre = document.createElement('div');
        nombre.className = 'buscador-item__nombre';
        nombre.textContent = f.nombre || '—';
        info.appendChild(nombre);

        var dni = document.createElement('div');
        dni.className = 'buscador-item__sub';
        dni.textContent = 'DNI ' + (f.dni || '—');
        info.appendChild(dni);

        // Tutor de la sección
        var tutor = document.createElement('div');
        tutor.className = 'buscador-item__sub';
        tutor.textContent = 'Tutor: ' + (f.tutor || 'sin asignar');
        info.appendChild(tutor);

        body.appendChild(info);

        // Ubicación académica
        var ubicacion = document.createElement('div');
        ubicacion.className = 'buscador-item__ubicacion';

        if (f.seccion) {
            var lugar = document.createElement('div');
            lugar.className = 'buscador-item__lugar';
            lugar.textContent = f.grado + ' "' + f.seccion + '"';
            ubicacion.appendChild(lugar);

            var nivel = document.createElement('div');
            nivel.className = 'buscador-item__nivel';
            nivel.textContent = f.nivel || '';
            ubicacion.appendChild(nivel);
        } else {
            var sinSeccion = document.createElement('div');
            sinSeccion.className = 'buscador-item__sin-seccion';
            sinSeccion.textContent = 'Sin sección asignada';
            ubicacion.appendChild(sinSeccion);
        }

        // Puesto en el orden de mérito del último bimestre cerrado (ranking por
        // grado). En el I Bimestre no hay orden de mérito vigente todavía.
        var puesto = document.createElement('div');
        if (!tieneOrden) {
            puesto.className = 'buscador-item__puesto buscador-item__puesto--vacio';
            puesto.textContent = 'No hay orden de mérito vigente';
        } else if (f.puesto) {
            puesto.className = 'buscador-item__puesto';
            puesto.textContent = 'Puesto ' + f.puesto + '.° del grado';
        } else {
            puesto.className = 'buscador-item__puesto buscador-item__puesto--vacio';
            puesto.textContent = 'Sin puesto aún';
        }
        ubicacion.appendChild(puesto);

        // Badge de estado
        var est = ESTADOS[f.estado] || { texto: f.estado || '—', clase: 'pendiente' };
        var badge = document.createElement('span');
        badge.className = 'buscador-badge buscador-badge--' + est.clase;
        badge.textContent = est.texto;
        ubicacion.appendChild(badge);

        body.appendChild(ubicacion);
        card.appendChild(body);
        return card;
    }

    function mostrarMensaje(texto, tipo) {
        resultados.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'buscador-mensaje buscador-mensaje--' + (tipo || 'info');
        div.textContent = texto;
        resultados.appendChild(div);
    }

    function mostrarVacio() {
        resultados.innerHTML = '';
    }

    function mostrarSpinner() {
        if (spinner) spinner.hidden = false;
        if (limpiar) limpiar.hidden = true;   // evita solape con el spinner
    }

    function ocultarSpinner() {
        if (spinner) spinner.hidden = true;
        toggleLimpiar();
    }

    function toggleLimpiar() {
        if (!limpiar) return;
        var cargando = spinner && !spinner.hidden;
        limpiar.hidden = input.value.trim() === '' || cargando;
    }
})();
