/**
 * cuadros-riesgo.js — SIGA-COCIAP
 *
 * Filtrado en cliente de la seccion "Estudiantes en riesgo" de /admin/cuadros.
 * Buscador por texto + chips de nivel + chips de grado, los tres en CONJUNCION.
 *
 * POR QUE EXISTE: medido el 07/09/2026, B1 lista 118 estudiantes repartidos en
 * 11 tablas. Sin filtro, ubicar a una persona concreta es recorrerlas a ojo.
 *
 * 🔴 LA BARRA DE FILTROS NACE `hidden` Y LA DESTAPA ESTE ARCHIVO. Si el JS no
 * carga, el informe se sigue leyendo entero —es lo que demuestra el A4, que no
 * lleva ningun control— en vez de quedarse con un buscador que no busca y unos
 * chips que no filtran. Misma regla que el componente de pestanas: sin JS la
 * pagina sigue siendo correcta.
 *
 * 🔴 NO TOCA EL <caption> DE CADA GRADO. Ese "(11 de 53)" es el dato del GRADO,
 * no de lo que se esta viendo: reescribirlo al filtrar convertiria un hecho del
 * bimestre en un artefacto de la vista. Lo que se ve lo dice el contador, que es
 * `role="status"` para que tambien lo oiga quien no ve desaparecer las filas.
 *
 * Se carga SIEMPRE en la pantalla, fuera del `if ($chartData)` de la vista: un
 * bimestre sin un solo grafico tambien necesita filtrar (y destapar la barra).
 */
(function () {
    const filtros = document.getElementById('riesgo-filtros');
    if (!filtros) return;   // otro bimestre, o el A4: no hay nada que filtrar

    const filas    = Array.prototype.slice.call(document.querySelectorAll('[data-riesgo-fila]'));
    const bloques  = Array.prototype.slice.call(document.querySelectorAll('[data-riesgo-bloque]'));
    const input    = document.getElementById('riesgo-buscar');
    const contador = document.getElementById('riesgo-contador');
    const sinRes   = document.getElementById('riesgo-sin-resultados');

    const chipsNivel = Array.prototype.slice.call(filtros.querySelectorAll('[data-riesgo-nivel]'));
    const chipsGrado = Array.prototype.slice.call(filtros.querySelectorAll('[data-riesgo-grado]'));

    const TOTAL = filas.length;

    // '' = "Todos" en los dos casos.
    let nivelSel = '';
    let gradoSel = '';

    /**
     * Mismo normalizador que `nomina.js`: minusculas, descompone en NFD y quita
     * las marcas diacriticas, para que "Nunez" encuentre a "Núñez" y al reves.
     *
     * El rango va escrito como \u0300-\u036f y NO con los caracteres combinantes
     * literales: son invisibles en el editor y no sobreviven bien a un cambio de
     * codificacion del archivo.
     *
     * Se normalizan LOS DOS LADOS aqui, en el JS. El servidor emite `data-buscar`
     * en crudo a proposito: con una normalizacion en PHP y otra en JS, basta que
     * una de las dos cambie para que el buscador falle en silencio justo con los
     * apellidos con tilde o con ñ, que es donde nadie prueba.
     */
    function normalizar(t) {
        return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function marcarChips(chips, attr, valor) {
        chips.forEach(function (c) {
            const activo = c.getAttribute(attr) === valor;
            c.classList.toggle('orden-chip--activo', activo);
            c.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });
    }

    /** Los chips de grado se acotan al nivel elegido: 11 chips a 5 o a 6. */
    function sincronizarChipsGrado() {
        chipsGrado.forEach(function (c) {
            const deNivel = c.getAttribute('data-riesgo-de-nivel');
            // El chip "Todos" no tiene nivel propio: nunca se oculta.
            c.hidden = deNivel !== null && nivelSel !== '' && deNivel !== nivelSel;
        });
    }

    function aplicar() {
        const q = normalizar(input ? input.value.trim() : '');
        let visibles = 0;

        filas.forEach(function (f) {
            const ok = (nivelSel === '' || f.getAttribute('data-nivel') === nivelSel)
                    && (gradoSel === '' || f.getAttribute('data-grado') === gradoSel)
                    && (q === '' || normalizar(f.getAttribute('data-buscar')).indexOf(q) !== -1);

            f.hidden = !ok;
            if (ok) visibles++;
        });

        // Un grado sin ninguna fila visible desaparece ENTERO. Dejar el bloque
        // con su rotulo y su encabezado y el cuerpo vacio se lee como un grado
        // sin casos, que es justo lo contrario de lo que pasa.
        bloques.forEach(function (b) {
            b.hidden = !b.querySelector('[data-riesgo-fila]:not([hidden])');
        });

        if (contador) {
            contador.textContent = 'Mostrando ' + visibles + ' de ' + TOTAL
                + (TOTAL === 1 ? ' estudiante.' : ' estudiantes.');
        }
        if (sinRes) sinRes.hidden = visibles !== 0;
    }

    chipsNivel.forEach(function (c) {
        c.addEventListener('click', function () {
            nivelSel = c.getAttribute('data-riesgo-nivel');
            // Cambiar de nivel devuelve el grado a "Todos": mantener un grado de
            // primaria seleccionado tras pulsar "Secundaria" deja la lista vacia
            // y parece que el filtro se rompio.
            gradoSel = '';
            marcarChips(chipsNivel, 'data-riesgo-nivel', nivelSel);
            marcarChips(chipsGrado, 'data-riesgo-grado', gradoSel);
            sincronizarChipsGrado();
            aplicar();
        });
    });

    chipsGrado.forEach(function (c) {
        c.addEventListener('click', function () {
            gradoSel = c.getAttribute('data-riesgo-grado');
            marcarChips(chipsGrado, 'data-riesgo-grado', gradoSel);
            aplicar();
        });
    });

    if (input) input.addEventListener('input', aplicar);

    // Ya hay con que filtrar: se destapa la barra y se pinta el estado inicial.
    filtros.hidden = false;
    sincronizarChipsGrado();
    aplicar();
})();
