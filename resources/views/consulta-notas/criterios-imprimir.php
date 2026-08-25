<?php
/**
 * Criterios de evaluacion — imprimible A4 vertical. Layout: print.
 *
 * NO reusa `criterios.php` a proposito: aquella pinta el arbol con <details>,
 * y un <details> cerrado no imprime su contenido. Aqui todo va desplegado.
 *
 * @var array  $periodo   { id, nombre_display, anio }
 * @var array  $secciones mismo arbol que la pantalla
 * @var int    $total
 * @var array  $filtros      { nivel, grado, seccion, docente } — se imprimen
 * @var array  $niveles
 * @var array  $grados
 * @var array  $seccionesCat
 * @var array  $docentes
 */
?>
<div class="criterios-print">

    <header class="criterios-print__head">
        <img class="criterios-print__logo" src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        <div class="criterios-print__titulo">
            <h1><?= e(config('institucion')) ?></h1>
            <p>Criterios de evaluacion &middot; <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?></p>
        </div>
    </header>

    <div class="criterios-print__meta">
        <span><strong>Criterios:</strong> <?= (int) $total ?></span>
        <span><strong>Secciones:</strong> <?= count($secciones) ?></span>
        <?php
        // Los filtros vigentes se IMPRIMEN: un listado parcial que no dice que
        // es parcial se lee como el listado completo.
        $activos = [];
        if (!empty($filtros['nivel'])   && isset($niveles[$filtros['nivel']]))     { $activos[] = $niveles[$filtros['nivel']]; }
        if (!empty($filtros['grado'])   && isset($grados[$filtros['grado']]))      { $activos[] = $grados[$filtros['grado']]; }
        if (!empty($filtros['seccion']) && isset($seccionesCat[$filtros['seccion']])) { $activos[] = $seccionesCat[$filtros['seccion']]['etiqueta']; }
        if (!empty($filtros['docente']) && isset($docentes[$filtros['docente']]))  { $activos[] = $docentes[$filtros['docente']]; }
        ?>
        <?php if ($activos): ?>
            <span><strong>Filtro:</strong> <?= e(implode(' · ', $activos)) ?></span>
        <?php endif; ?>
        <span><strong>Impreso:</strong> <?= e(date('d/m/Y H:i')) ?></span>
    </div>

    <p class="criterios-print__nota">
        Solo lectura. Incluye unicamente los criterios de competencias aprobadas y
        bloqueadas por su docente.
    </p>

    <?php foreach ($secciones as $s): ?>
        <section class="criterios-print__seccion">
            <h2 class="criterios-print__seccion-tit">
                <?= e($s['grado_nombre'] . ' ' . $s['seccion_nombre']) ?>
                <span><?= e($s['nivel_nombre']) ?> &middot; <?= (int) $s['n_criterios'] ?> criterio(s)</span>
            </h2>

            <?php foreach ($s['cargas'] as $c): ?>
                <?php // Separador como CARACTER: la cadena pasa por e(). Ver criterios.php.
                      $area = $c['subarea_nombre']
                          ? $c['area_nombre'] . ' — ' . $c['subarea_nombre']
                          : $c['area_nombre']; ?>
                <table class="criterios-print__tabla">
                    <caption>
                        <span class="criterios-print__area"><?= e($area) ?></span>
                        <span class="criterios-print__docente"><?= e($c['docente']) ?></span>
                    </caption>
                    <thead>
                        <tr>
                            <th class="col-comp">Competencia</th>
                            <th class="col-crit">Criterio</th>
                            <th class="col-desc">Descripcion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($c['competencias'] as $comp): ?>
                            <?php $filas = count($comp['criterios']); $i = 0; ?>
                            <?php foreach ($comp['criterios'] as $cr): ?>
                                <?php $desc = trim((string) $cr['descripcion']); ?>
                                <tr>
                                    <?php if ($i === 0): ?>
                                        <td class="col-comp" rowspan="<?= $filas ?>">
                                            <?= e($comp['nombre']) ?>
                                            <?php if (!empty($comp['es_transversal'])): ?>
                                                <span class="criterios-print__tag">transversal</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="col-crit"><?= e($cr['nombre']) ?></td>
                                    <td class="col-desc<?= $desc === '' ? ' col-desc--vacia' : '' ?>">
                                        <?= $desc === '' ? '&mdash;' : e($desc) ?>
                                    </td>
                                </tr>
                                <?php $i++; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
