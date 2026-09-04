<?php
/**
 * Estudiantes en riesgo: los que acumulan RIESGO_MIN_C competencias en C o más,
 * agrupados por grado. Compartido por la pantalla y el imprimible A4.
 *
 * 🔴 SALE DEL MOTOR OFICIAL DEL MÉRITO, no de un promedio suelto. `num_c`,
 * `puesto` y `promedio_general` vienen de la MISMA fila de
 * `OrdenMeritoModel::rankingGrado`, así que las tres cifras de una fila no
 * pueden contradecirse entre sí: se cuentan sobre el mismo universo (solo
 * competencias BLOQUEADAS, sin transversales salvo Ética en secundaria, sin
 * áreas exoneradas y sin notas extraordinarias).
 *
 * 🔴 NO ES EL MISMO "EN RIESGO" QUE EL DEL BLOQUE DE CALIFICACIONES. Aquél es
 * el promedio general por debajo de NOTA_MIN_B, contado por NIVEL
 * (`AnioAcademicoModel::getResumenBimestre`). Son dos preguntas distintas y las
 * cifras se separan mucho —medido el 04/09/2026 en B2: 0 por promedio, 77 por
 * número de C—, así que el pie de esta sección lo dice con todas las letras. Sin
 * esa aclaración la misma pantalla muestra dos números bajo el mismo rótulo.
 *
 * El filtrado de grados vive AQUI y no en cada vista: la pantalla y el A4 lo
 * comparten, y duplicarlo es como acabarian mostrando listas distintas.
 *
 * @var array $bloques  salida de CuadrosEstadisticosController::componerBloques
 */

// Solo los grados que aportan a alguien: un bloque vacio con su rotulo dice que
// el grado no se evaluo, cuando lo que pasa es que nadie llego al umbral.
$riesgoGrados = array_values(array_filter(
    $bloques['merito']['por_grado'] ?? [],
    static fn(array $g): bool => !empty($g['en_riesgo'])
));
$riesgoMinC = (int) ($bloques['merito']['riesgo_min_c'] ?? 3);

if (empty($riesgoGrados)) {
    return;
}
?>

<?php // UNA TABLA POR GRADO, no una sola con columna "Grado". Es el patron que
      // ya resolvio el listado de inasistencias (T2) en esta misma pantalla:
      // con muchas filas seguidas el encabezado de columnas se va de pantalla y
      // los numeros dejan de significar nada. Medido: 118 filas en B1.
      //
      // El rotulo va en un <caption>, NO en un <hN>: queda asociado a SU tabla
      // para un lector de pantalla y no depende del nivel de encabezado del
      // contenedor, que es <h3> en la pantalla y <h2> en el imprimible. ?>
<?php foreach ($riesgoGrados as $g): ?>
    <div class="cuadros-top__bloque cuadros-top__bloque--riesgo">
        <div class="tabla-notas-wrapper">
            <table class="tabla-notas cuadros-top cuadros-top--riesgo">
                <caption class="cuadros-top__caption">
                    <?= e($g['grado']['nombre_display'] . ' — ' . $g['grado']['nivel_nombre']) ?>
                    <span class="text-muted text-sm">
                        (<?= count($g['en_riesgo']) ?> de <?= (int) $g['total'] ?>)
                    </span>
                </caption>
                <thead>
                    <tr>
                        <th class="col-nombre">Estudiante</th>
                        <th>Sección</th>
                        <th class="text-center cuadros-top__n">Puesto</th>
                        <th class="text-center cuadros-top__n">Promedio</th>
                        <th class="text-center cuadros-top__n">C</th>
                        <th class="text-center cuadros-top__n">Competencias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($g['en_riesgo'] as $al): ?>
                        <tr>
                            <td class="col-nombre"><?= e($al['nombre_completo']) ?></td>
                            <td><?= e($al['seccion_nombre']) ?></td>
                            <td class="text-center text-muted">
                                <?= (int) $al['puesto'] ?> de <?= (int) $g['total'] ?>
                            </td>
                            <td class="text-center">
                                <?= e(number_format((float) $al['promedio_general'], 2)) ?>
                            </td>
                            <?php // `--destaca` marca el dato que trae a esta persona a la
                                  // lista, igual que en el listado de inasistencias: sin el,
                                  // una fila con buen promedio y 6 C parece un error. ?>
                            <td class="text-center cuadros-top__v--destaca"><?= (int) $al['num_c'] ?></td>
                            <td class="text-center text-muted"><?= (int) $al['num_competencias'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<?php // El pie es UNO SOLO y al final, fuera de todos los bloques: repetirlo en
      // cada grado seria ruido, y la aclaracion de que hay DOS "en riesgo" en
      // esta pantalla pesa mas leida una vez que diluida once veces. Va suelto
      // (`--suelto`) porque ya no cuelga de ningun wrapper: dentro se desplaza
      // con el scroll horizontal y la card lo recorta por su overflow. ?>
<div class="tabla-pie tabla-pie--suelto">
    <p class="text-sm text-muted">
        Se lista a cada estudiante con <strong><?= (int) $riesgoMinC ?> competencias en C
        o más</strong> en el bimestre, ordenado de más C a menos; a igual número de C,
        primero el promedio más bajo. No hay tope por grado: un grado puede aportar una
        fila o quince, y un grado sin ningún caso no aparece.
    </p>
    <p class="text-sm text-muted">
        Las cifras salen del <strong>orden de mérito</strong>: cuentan solo las competencias
        ya bloqueadas por el docente o por el cierre, sin las áreas exoneradas ni las notas
        extraordinarias. En un bimestre abierto la lista <strong>crece conforme se bloquean
        competencias</strong>.
    </p>
    <p class="text-sm text-muted">
        ⚠ No es la misma cifra que <strong>«En riesgo»</strong> del bloque de
        Calificaciones, que cuenta a quienes tienen el <strong>promedio general</strong> por
        debajo de <?= (int) NOTA_MIN_B ?>, agrupados por nivel. Un estudiante puede acumular
        varias C y aun así aprobar de promedio.
    </p>
</div>
