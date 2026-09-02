<?php
/**
 * Parcial: estadisticas de UNA competencia, encima de su tabla de alumnos.
 *
 * Lo comparten cuatro pantallas —el resumen del docente, la consulta de notas por
 * carga, el historial del docente en un bimestre cerrado y el panel de tutoria—,
 * por eso vive en shared/ y no en la carpeta de un modulo. Basta con tener los
 * criterios CONFIRMADOS: no exige que la competencia este aprobada y bloqueada.
 *
 * No consulta nada: todo sale de un array que ya esta en memoria. El calculo vive
 * en stats_competencia() (helpers.php), que es su punto unico; aqui solo se pinta.
 *
 * ENTRADA — dos formas, segun lo que tenga a mano la vista anfitriona:
 *
 *   a) Por convencion, cuando la vista ya trabaja con UNA competencia:
 *      @var array  $alumnos      filas con matricula_id, promedio y literal
 *      @var array  $exonerados   matricula_ids exonerados de la carga
 *      @var string $nivelCodigo  'prim' | 'sec'
 *
 *   b) Con prefijo, cuando la vista pinta VARIAS competencias en una sola tabla
 *      (tutoria: TIC y GAMA) y no puede pisar sus propias variables:
 *      $statsAlumnos, $statsExonerados, $statsNivel y $statsTitulo (encabezado
 *      del bloque, para distinguir un bloque del otro).
 *
 * ⚠️ Las cuatro se LIMPIAN al final. El parcial se requiere DENTRO DE UN BUCLE en
 * `consulta-notas/carga.php` y en `docente/tutoria.php`: sin el unset, la segunda
 * vuelta heredaria los datos de la primera y pintaria dos veces las mismas cifras.
 */
$statsAlumnos    = $statsAlumnos    ?? $alumnos;
$statsExonerados = $statsExonerados ?? ($exonerados ?? []);
$statsNivel      = $statsNivel      ?? $nivelCodigo;
$statsTitulo     = $statsTitulo     ?? null;

$stats = stats_competencia($statsAlumnos, $statsExonerados, $statsNivel);

// Sin nadie a quien contar (roster vacio o todos exonerados) no se pinta nada:
// una fila de ceros no informa, ocupa y confunde.
if ($stats['universo'] === 0):
    unset($statsAlumnos, $statsExonerados, $statsNivel, $statsTitulo);
    return;
endif;

$litLeyenda = [
    ['AD', 'Logro destacado'],
    ['A',  'Logro esperado'],
    ['B',  'En proceso'],
    ['C',  'En inicio'],
];

// Formatea 40.0 como "40" y 33.3 como "33.3".
$pct = static fn(float $v): string => rtrim(rtrim(number_format($v, 1), '0'), '.');

// El corte de aprobacion cambia con el nivel y hay que decirlo, o el mismo numero
// se lee distinto en primaria y en secundaria. Sale del helper YA normalizado:
// resolverlo aqui obligaria a repetir el 'prim'/'sec' y esta vista recibe el
// nivel en las dos formas ('prim' y 'primaria', segun quien la monte).
$cortes = implode(' + ', $stats['aprobatorios']);
?>
<section class="stats-comp">
    <?php if ($statsTitulo !== null): ?>
        <h3 class="stats-comp__titulo"><?= e($statsTitulo) ?></h3>
    <?php endif; ?>

    <div class="stats-comp__kpis">
        <div class="stats-comp__kpi">
            <span class="stats-comp__n"><?= $stats['evaluados'] ?></span>
            <span class="stats-comp__t">Evaluados</span>
        </div>
        <div class="stats-comp__kpi">
            <span class="stats-comp__n stats-comp__n--muted"><?= $stats['no_evaluados'] ?></span>
            <span class="stats-comp__t">Sin evaluar</span>
        </div>
        <div class="stats-comp__kpi">
            <span class="stats-comp__n stats-comp__n--ok"><?= $stats['aprobados'] ?></span>
            <span class="stats-comp__t">Aprobados (<?= e($cortes) ?>)</span>
            <?php if ($stats['evaluados'] > 0): ?>
                <span class="stats-comp__kpi-pct"><?= e($pct($stats['pct']['aprobados'])) ?>%</span>
            <?php endif; ?>
        </div>
        <div class="stats-comp__kpi">
            <span class="stats-comp__n stats-comp__n--err"><?= $stats['desaprobados'] ?></span>
            <span class="stats-comp__t">Desaprobados</span>
            <?php if ($stats['evaluados'] > 0): ?>
                <span class="stats-comp__kpi-pct"><?= e($pct($stats['pct']['desaprobados'])) ?>%</span>
            <?php endif; ?>
        </div>
        <?php if ($stats['exonerados'] > 0): ?>
            <div class="stats-comp__kpi">
                <span class="stats-comp__n stats-comp__n--muted"><?= $stats['exonerados'] ?></span>
                <span class="stats-comp__t">Exonerados &middot; fuera del conteo</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($stats['evaluados'] === 0): ?>
        <p class="stats-comp__vacio">
            Todavía no hay estudiantes con promedio en esta competencia.
        </p>
    <?php else: ?>
        <?php
        // La barra es decorativa: cada cifra que muestra esta tambien en la
        // leyenda de abajo, en texto. El aria-label la resume para quien no la ve.
        $resumenBarra = [];
        $tramos       = [];
        foreach ($litLeyenda as [$lit, $desc]) {
            $resumenBarra[] = $lit . ': ' . $stats['literales'][$lit];
            if ($stats['literales'][$lit] > 0) {
                $tramos[] = $lit;
            }
        }

        // FRACCIONES (`fr`), no anchos en `%`. La rejilla descuenta los `gap`
        // ANTES de repartir las fracciones, asi que los tramos pueden ir
        // separados sin que las proporciones dejen de ser las reales — con flex
        // y `%` era imposible, porque el hueco desbordaba el 100 % y flex los
        // encogia. De paso desaparece el hueco de fondo que asomaba cuando los
        // porcentajes redondeados no llegaban a sumar 100.
        //
        // `minmax(0, …)` para que la etiqueta de dentro NUNCA imponga un ancho
        // minimo al tramo: la proporcion manda sobre el texto, no al reves.
        // `number_format` y no el float pelado: fija el separador decimal.
        $columnas = implode(' ', array_map(
            static fn(string $lit): string
                => 'minmax(0, ' . number_format($stats['pct'][$lit], 1, '.', '') . 'fr)',
            $tramos
        ));
        ?>
        <div class="stats-comp__barra" role="img"
             style="grid-template-columns: <?= $columnas ?>;"
             aria-label="Distribución de <?= $stats['evaluados'] ?> estudiantes evaluados. <?= e(implode('. ', $resumenBarra)) ?>.">
            <?php foreach ($tramos as $lit): ?>
                <?php
                // Que un tramo lleve su cifra dentro depende de si CABE, y eso es
                // un ancho en pixeles, no un porcentaje. La etiqueta mas larga
                // ("AD 18.5%") mide 58px contando el borde del tramo, y la barra
                // ocupa el ancho de `.app-main` (1200px como tope, sin barra
                // lateral) menos 82px de rellenos. De ahi salen los dos umbrales:
                //
                //   · >= 25 %  cabe SIEMPRE, hasta en una ventana de 320px
                //              (254px de barra x 0.25 = 63px).
                //   · >=  8 %  cabe solo a partir de 900px de ventana
                //              (818px de barra x 0.08 = 65px). Ese tramo sale con
                //              la clase `--media` y lo enciende la media query.
                //   · <   8 %  no se etiqueta: su cifra esta en la leyenda, que
                //              lista los CUATRO literales pase lo que pase.
                //
                // Si no cupiera, el `overflow: hidden` del tramo la cortaria a
                // media palabra. Fallar hacia "sin etiqueta" es lo correcto.
                $p = $stats['pct'][$lit];
                $claseEtq = $p >= 25 ? 'stats-comp__seg-etq'
                          : ($p >= 8 ? 'stats-comp__seg-etq stats-comp__seg-etq--media' : null);
                ?>
                <span class="stats-comp__seg stats-comp__seg--<?= strtolower($lit) ?>"
                      title="<?= $lit ?>: <?= $stats['literales'][$lit] ?> de <?= $stats['evaluados'] ?> evaluados (<?= e($pct($p)) ?>%)">
                    <?php if ($claseEtq !== null): ?>
                        <?php // Mismo formateador que la leyenda: la barra y el texto
                              // de abajo dicen EXACTAMENTE la misma cifra, con su
                              // decimal. Nada se redondea al entero. ?>
                        <span class="<?= $claseEtq ?>"><?= $lit ?> <?= e($pct($p)) ?>%</span>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>

        <ul class="stats-comp__leyenda">
            <?php foreach ($litLeyenda as [$lit, $desc]): ?>
                <li class="stats-comp__item">
                    <span class="nota-literal nota-literal--<?= strtolower($lit) ?> stats-comp__chip"
                          title="<?= e($desc) ?>"><?= $lit ?></span>
                    <span class="stats-comp__cant"><?= $stats['literales'][$lit] ?></span>
                    <span class="stats-comp__pct">(<?= e($pct($stats['pct'][$lit])) ?>%)</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php
// Ver la nota del encabezado: sin esto, la siguiente vuelta del bucle que monta
// este parcial reutilizaria los datos de la anterior.
unset($statsAlumnos, $statsExonerados, $statsNivel, $statsTitulo);
