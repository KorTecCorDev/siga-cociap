<?php
/**
 * Vista: Cuadros estadísticos — tablero de indicadores del bimestre (24/08/2026).
 *
 * Reúne los cinco registros en una pantalla. Cada cifra viene ya calculada por
 * el modelo que la posee; esta vista solo pinta. Es de SOLO LECTURA.
 *
 * Conducta y asistencia se organizan en PESTAÑAS (componente global `.tabs` +
 * `js/tabs.js`): entre las dos suman siete gráficos y dos tablas largas, y en
 * una sola columna serían varias pantallas de scroll. El imprimible A4 no lleva
 * pestañas y muestra todo desplegado.
 *
 * @var array      $periodos
 * @var array|null $periodo
 * @var array|null $bloques  { matricula, calificaciones, evolucion, merito,
 *                             empates, reaperturas, conducta,
 *                             conducta_secciones, asistencia,
 *                             conducta_literales, conducta_criterios,
 *                             asistencia_secciones, asistencia_top,
 *                             asistencia_evolucion }
 */
$pct = static fn(int $parte, int $total): int => $total > 0 ? (int) round($parte / $total * 100) : 0;

// Los datos de los graficos se arman en un partial COMPARTIDO con el
// imprimible: si cada vista los calculara por su cuenta, el papel podria
// dibujar algo distinto de la pantalla para el mismo bimestre.
require VIEW_PATH . '/admin/cuadros/_chart-data.php';
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">&larr; Dashboard</a>
    <div>
        <h1 class="page-title">Cuadros estadísticos</h1>
        <p class="page-subtitle">
            Indicadores de matrícula, calificaciones, orden de mérito, conducta y asistencia.
            Solo lectura.
        </p>
    </div>
</div>

<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= url('admin/cuadros') ?>" class="form-inline">
            <label for="periodo_id" class="form-label">Bimestre</label>
            <select name="periodo_id" id="periodo_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $periodo && (int) $p['id'] === (int) $periodo['id'] ? 'selected' : '' ?>>
                        <?= e($p['nombre_display']) ?> <?= e($p['anio']) ?>
                        (<?= e($p['estado']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn--primary btn--sm">Ver</button></noscript>
        </form>
        <?php if ($periodo): ?>
            <a href="<?= url('admin/cuadros/imprimir?periodo_id=' . (int) $periodo['id']) ?>"
               class="btn btn--secondary btn--sm" target="_blank" rel="noopener">
                &#128424; Imprimir
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$periodo || !$bloques): ?>
    <div class="empty-state"><p>No hay bimestres disponibles.</p></div>
<?php else: ?>

<?php // ── 1. MATRÍCULA ────────────────────────────────────────────── ?>
<?php $k = $bloques['matricula']['kpis']; ?>
<section class="dash-grupo" aria-labelledby="cuadros-g-matricula">
    <h2 id="cuadros-g-matricula" class="dash-grupo__titulo">Matrícula del año</h2>
    <div class="cuadros-kpis mb-lg">
        <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['aprobadas'] ?></span><span class="cuadros-kpi__t">Aprobadas</span></div>
        <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['pendientes'] ?></span><span class="cuadros-kpi__t">Pendientes</span></div>
        <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['desactivadas'] ?></span><span class="cuadros-kpi__t">Desactivadas</span></div>
        <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['secciones'] ?></span><span class="cuadros-kpi__t">Secciones</span></div>
        <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= e((string) $k['promedio_seccion']) ?></span><span class="cuadros-kpi__t">Promedio por sección</span></div>
    </div>
    <p class="text-sm text-muted">
        Detalle por grado, tipo y género en <a href="<?= url('matriculas/resumen') ?>">Resumen de matrículas</a>.
    </p>
</section>

<?php // ── 2. CALIFICACIONES ───────────────────────────────────────── ?>
<section class="dash-grupo" aria-labelledby="cuadros-g-calificaciones">
    <h2 id="cuadros-g-calificaciones" class="dash-grupo__titulo">Calificaciones del bimestre</h2>
    <?php if (empty($bloques['calificaciones']['niveles'])): ?>
        <div class="empty-state"><p>Este bimestre todavía no tiene calificaciones registradas.</p></div>
    <?php else: ?>
    <div class="tabla-responsive">
        <table class="tabla-resumen">
            <thead>
                <tr>
                    <th>Nivel</th>
                    <th class="text-center">Calificaciones</th>
                    <th class="text-center">AD</th>
                    <th class="text-center">A</th>
                    <th class="text-center">B</th>
                    <th class="text-center">C</th>
                    <th class="text-center">% en logro</th>
                    <th class="text-center">Estudiantes</th>
                    <th class="text-center">En riesgo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bloques['calificaciones']['niveles'] as $n): ?>
                    <tr>
                        <td><strong><?= e($n['nivel_nombre']) ?></strong></td>
                        <td class="text-center"><?= (int) $n['total_calif'] ?></td>
                        <td class="text-center"><?= (int) $n['ad'] ?></td>
                        <td class="text-center"><?= (int) $n['a'] ?></td>
                        <td class="text-center"><?= (int) $n['b'] ?></td>
                        <td class="text-center"><?= (int) $n['c'] ?></td>
                        <td class="text-center"><strong><?= (int) $n['pct_logro'] ?>%</strong></td>
                        <td class="text-center"><?= (int) $n['total_estudiantes'] ?></td>
                        <td class="text-center<?= (int) $n['en_riesgo'] > 0 ? ' text-danger' : '' ?>"><?= (int) $n['en_riesgo'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    // G1 — Donut de literales, barra logro/proceso, riesgo e histograma de C.
    // Se REUSA el panel de /director/periodos/{id}/stats en lugar de repintar
    // las mismas reglas: recibe la misma variable $resumen y sus estilos viven
    // en la raiz de _anio-academico.scss, no anidados, asi que es portatil.
    $resumen = $bloques['calificaciones'];
    ?>
    <div class="cuadros-panel">
        <?php require VIEW_PATH . '/director/anios/_panel-bimestre.php'; ?>
    </div>

    <?php if (isset($chartData['evolucion'])): ?>
    <div class="cuadros-charts">
        <div class="card cuadros-chart cuadros-chart--ancho">
            <div class="card__header"><h3 class="card__title">Evolución del % en logro por bimestre</h3></div>
            <div class="card__body">
                <div id="chart-evolucion"></div>
                <p class="cuadros-nota">
                    Porcentaje de calificaciones en AD o A sobre el total del nivel.
                    Solo aparecen los bimestres en los que <strong>todos</strong> los niveles ya
                    tienen notas: incluir uno que recién arranca mostraría un salto que no es
                    una mejora, sino una muestra todavía sin representatividad.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>

<?php // ── 3. ORDEN DE MÉRITO ──────────────────────────────────────── ?>
<section class="dash-grupo" aria-labelledby="cuadros-g-merito">
    <h2 id="cuadros-g-merito" class="dash-grupo__titulo">Orden de mérito</h2>
    <?php if (!empty($bloques['empates'])): ?>
        <p class="text-danger">
            ⚠ Hay <?= count($bloques['empates']) ?> grado(s) con empates sin resolver: el orden no es oficializable
            hasta resolverlos en <a href="<?= url('director/orden-merito') ?>">Orden de mérito</a>.
        </p>
    <?php endif; ?>
    <?php if (empty($bloques['merito']['por_grado'])): ?>
        <div class="empty-state"><p>Este bimestre todavía no tiene ranking.</p></div>
    <?php else: ?>
    <div class="tabla-responsive">
        <table class="tabla-resumen">
            <thead>
                <tr>
                    <th>Grado</th>
                    <th>Nivel</th>
                    <th class="text-center">Estudiantes en el ranking</th>
                    <th>Primer puesto</th>
                    <th class="text-center">Promedio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bloques['merito']['por_grado'] as $g): ?>
                    <tr>
                        <td><strong><?= e($g['grado']['nombre_display']) ?></strong></td>
                        <td><?= e($g['grado']['nivel_nombre']) ?></td>
                        <td class="text-center"><?= (int) $g['total'] ?></td>
                        <td><?= e($g['mejor']['nombre_completo'] ?? '—') ?></td>
                        <td class="text-center"><?= e((string) ($g['mejor']['promedio_general'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($chartData['brecha'])): ?>
    <div class="cuadros-charts">
        <div class="card cuadros-chart cuadros-chart--ancho">
            <div class="card__header"><h3 class="card__title">Brecha interna de cada grado</h3></div>
            <div class="card__body">
                <div id="chart-brecha"></div>
                <p class="cuadros-nota">
                    Promedio del primer puesto frente al del último, por grado. La distancia
                    entre ambas barras es la dispersión del grado, no su nivel.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>

<?php // ── 4. CONDUCTA ─────────────────────────────────────────────── ?>
<?php
require VIEW_PATH . '/admin/cuadros/_resumen-conducta-asistencia.php';
$pid = (int) $periodo['id'];
?>
<section class="dash-grupo" aria-labelledby="cuadros-g-conducta">
    <h2 id="cuadros-g-conducta" class="dash-grupo__titulo">Conducta</h2>

    <?php // Los tres bloques de conducta responden preguntas distintas —cómo
          // salió, cómo va el proceso, qué normas cuestan— y juntos no caben en
          // una pantalla. El servidor deja abierto RESULTADOS; tabs.js recuerda
          // el último elegido POR BIMESTRE. ?>
    <div class="tabs" role="tablist" aria-label="Bloques de conducta"
         data-tabs="conducta" data-tabs-memoria="cuadros.tab.conducta.<?= $pid ?>">
        <button type="button" class="tab tab--activa" role="tab" id="tab-cond-resultados"
                data-tab="cond-resultados" aria-controls="panel-cond-resultados" aria-selected="true">
            Resultados
        </button>
        <button type="button" class="tab" role="tab" id="tab-cond-proceso"
                data-tab="cond-proceso" aria-controls="panel-cond-proceso" aria-selected="false" tabindex="-1">
            Proceso de cierre
        </button>
        <button type="button" class="tab" role="tab" id="tab-cond-criterios"
                data-tab="cond-criterios" aria-controls="panel-cond-criterios" aria-selected="false" tabindex="-1">
            Criterios de convivencia
        </button>
    </div>

    <?php // ── Conducta · Resultados ──────────────────────────────── ?>
    <div id="panel-cond-resultados" class="tab-panel" role="tabpanel"
         data-panel="cond-resultados" aria-labelledby="tab-cond-resultados">
        <?php if ($lit['total'] > 0): ?>
            <div class="cuadros-kpis mb-md">
                <div class="cuadros-kpi">
                    <span class="cuadros-kpi__n"><?= $lit['logro'] ?></span>
                    <span class="cuadros-kpi__t">En logro (AD + A) &middot; <?= $pctLogro ?>%</span>
                </div>
                <div class="cuadros-kpi">
                    <span class="cuadros-kpi__n"><?= $lit['b'] ?></span>
                    <span class="cuadros-kpi__t">En proceso (B)</span>
                </div>
                <div class="cuadros-kpi">
                    <span class="cuadros-kpi__n"><?= $lit['c'] ?></span>
                    <span class="cuadros-kpi__t">En inicio (C)</span>
                </div>
                <?php if ($delta !== null): ?>
                <div class="cuadros-kpi">
                    <span class="cuadros-kpi__n"><?= $delta > 0 ? '+' : '' ?><?= number_format($delta, 1) ?></span>
                    <span class="cuadros-kpi__t">Puntos frente a <?= e($nombrePrevio) ?></span>
                </div>
                <?php endif; ?>
                <div class="cuadros-kpi">
                    <span class="cuadros-kpi__n"><?= $lit['total'] ?></span>
                    <span class="cuadros-kpi__t">Estudiantes con conducta calificada</span>
                </div>
            </div>

            <p class="cuadros-nota mb-md">
                Cuenta a todo estudiante con conducta calificada, incluidas las secciones
                que todavía no han cerrado el bimestre.
            </p>
        <?php else: ?>
            <p class="empty-state">Todavía no hay conducta calificada en este bimestre.</p>
        <?php endif; ?>

        <?php // La EVOLUCIÓN va fuera del `if` de arriba a propósito: es la serie
              // histórica, y en el bimestre en curso —que todavía no tiene dato
              // propio— es justo cuando más sirve para comparar. Meterla dentro
              // la hacía desaparecer del bimestre activo. ?>
        <?php if (isset($chartData['conductaLiterales']) || isset($chartData['conductaEvolucion'])): ?>
        <div class="cuadros-charts">
            <?php if (isset($chartData['conductaLiterales'])): ?>
            <div class="card cuadros-chart">
                <div class="card__header"><h3 class="card__title">Distribución por nivel</h3></div>
                <div class="card__body">
                    <div id="chart-conducta-literales"></div>
                    <p class="cuadros-nota">
                        Reparto AD / A / B / C en el bimestre a la vista. La altura de cada barra
                        es cuántos estudiantes tienen conducta calificada en ese nivel.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($chartData['conductaEvolucion'])): ?>
            <div class="card cuadros-chart">
                <div class="card__header"><h3 class="card__title">Evolución del % en logro</h3></div>
                <div class="card__body">
                    <div id="chart-conducta-evolucion"></div>
                    <p class="cuadros-nota">
                        Porcentaje en AD o A por bimestre. Solo aparecen los bimestres en que
                        ambos niveles tienen conducta registrada.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php // ── Conducta · Proceso de cierre ───────────────────────── ?>
    <div id="panel-cond-proceso" class="tab-panel" role="tabpanel"
         data-panel="cond-proceso" aria-labelledby="tab-cond-proceso" hidden>
        <div class="cuadros-kpis mb-md">
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= (int) $c['cerradas'] ?>/<?= (int) $c['secciones'] ?></span>
                <span class="cuadros-kpi__t">Conducta cerrada</span>
            </div>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= (int) $c['pend_tutor'] ?></span>
                <span class="cuadros-kpi__t">Esperan al tutor</span>
            </div>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= (int) $c['pend_auxiliar'] ?></span>
                <span class="cuadros-kpi__t">Esperan al auxiliar</span>
            </div>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= $pct((int) $c['calificados'], (int) $c['esperados']) ?>%</span>
                <span class="cuadros-kpi__t">Estudiantes calificados en conducta</span>
            </div>
        </div>

        <?php if (isset($chartData['conductaEmbudo']) || isset($chartData['conductaSecciones'])): ?>
        <div class="cuadros-charts">
            <?php if (isset($chartData['conductaEmbudo'])): ?>
            <div class="card cuadros-chart">
                <div class="card__header"><h3 class="card__title">Etapa del cierre de conducta</h3></div>
                <div class="card__body">
                    <div id="chart-conducta-embudo"></div>
                    <p class="cuadros-nota">
                        Secciones por etapa: el auxiliar bloquea primero y el tutor cierra después.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($chartData['conductaSecciones'])): ?>
            <div class="card cuadros-chart">
                <div class="card__header"><h3 class="card__title">Secciones con menor avance</h3></div>
                <div class="card__body">
                    <div id="chart-conducta-secciones"></div>
                    <p class="cuadros-nota">
                        Porcentaje de estudiantes ya calificados en conducta. (P) primaria, (S) secundaria.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php // ── Conducta · Criterios ───────────────────────────────── ?>
    <div id="panel-cond-criterios" class="tab-panel" role="tabpanel"
         data-panel="cond-criterios" aria-labelledby="tab-cond-criterios" hidden>
        <?php if (empty($condCrit['criterios'])): ?>
            <div class="alert alert--info">
                Este bimestre no tiene grilla de criterios: se registró con el modelo
                anterior, que guardaba el literal de conducta directamente.
            </div>
        <?php else: ?>
            <?php if (isset($chartData['conductaCriterios'])): ?>
            <div class="card cuadros-chart cuadros-chart--ancho mb-md">
                <div class="card__header"><h3 class="card__title">Criterios con mayor incumplimiento</h3></div>
                <div class="card__body">
                    <div id="chart-conducta-criterios"></div>
                    <p class="cuadros-nota">
                        Porcentaje de respuestas &laquo;No cumple&raquo; sobre el total registrado
                        en el colegio. Pasa el cursor por una barra para leer el criterio completo.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card__header"><h3 class="card__title">Incumplimiento por sección</h3></div>
                <div class="card__body">
                    <?php require VIEW_PATH . '/admin/cuadros/_matriz-criterios.php'; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <p class="text-sm text-muted">
        El detalle por sección está en <a href="<?= url('consulta-notas?periodo_id=' . $pid) ?>">Consulta de notas</a>.
    </p>
</section>

<?php // ── 5. ASISTENCIA ───────────────────────────────────────────── ?>
<section class="dash-grupo" aria-labelledby="cuadros-g-asistencia">
    <h2 id="cuadros-g-asistencia" class="dash-grupo__titulo">Asistencia</h2>

    <div class="tabs" role="tablist" aria-label="Bloques de asistencia"
         data-tabs="asistencia" data-tabs-memoria="cuadros.tab.asistencia.<?= $pid ?>">
        <button type="button" class="tab tab--activa" role="tab" id="tab-asis-panorama"
                data-tab="asis-panorama" aria-controls="panel-asis-panorama" aria-selected="true">
            Panorama
        </button>
        <button type="button" class="tab" role="tab" id="tab-asis-secciones"
                data-tab="asis-secciones" aria-controls="panel-asis-secciones" aria-selected="false" tabindex="-1">
            Comparativa por sección
        </button>
        <button type="button" class="tab" role="tab" id="tab-asis-top"
                data-tab="asis-top" aria-controls="panel-asis-top" aria-selected="false" tabindex="-1">
            Estudiantes con más faltas
        </button>
    </div>

    <?php // ── Asistencia · Panorama ──────────────────────────────── ?>
    <div id="panel-asis-panorama" class="tab-panel" role="tabpanel"
         data-panel="asis-panorama" aria-labelledby="tab-asis-panorama">
        <div class="cuadros-kpis mb-md">
            <?php // Sin NINGÚN registro de asistencia, «0 faltas» y «100 % sin
                  // incidencias» serían datos falsos, no ausentes: nadie ha
                  // tomado asistencia todavía. Es el mismo error que ya se
                  // corrigió una vez en la boleta. ?>
            <?php if ((int) $a['registrados'] > 0): ?>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= $asisTot['faltas'] ?></span>
                <span class="cuadros-kpi__t">Faltas sin justificar</span>
            </div>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= $asisTot['tardanzas'] ?></span>
                <span class="cuadros-kpi__t">Tardanzas sin justificar</span>
            </div>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= $pct($asisTot['sin_incidencias'], $asisTot['alumnos']) ?>%</span>
                <span class="cuadros-kpi__t">Estudiantes sin ninguna incidencia</span>
            </div>
            <?php endif; ?>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= (int) $a['completas'] ?>/<?= (int) $a['secciones'] ?></span>
                <span class="cuadros-kpi__t">Asistencia completa por sección</span>
            </div>
            <div class="cuadros-kpi">
                <span class="cuadros-kpi__n"><?= $pct((int) $a['registrados'], (int) $a['esperados']) ?>%</span>
                <span class="cuadros-kpi__t">Cobertura del registro de asistencia</span>
            </div>
        </div>
        <?php if ((int) $a['registrados'] === 0): ?>
            <p class="empty-state">Todavía no se ha registrado asistencia en este bimestre.</p>
        <?php endif; ?>

        <?php if (isset($chartData['asisEvolucion']) || isset($chartData['asisJustificacion'])): ?>
        <div class="cuadros-charts">
            <?php if (isset($chartData['asisEvolucion'])): ?>
            <div class="card cuadros-chart">
                <div class="card__header"><h3 class="card__title">Evolución en el año</h3></div>
                <div class="card__body">
                    <div id="chart-asis-evolucion"></div>
                    <p class="cuadros-nota">
                        Total de faltas y tardanzas sin justificar por bimestre. Solo aparecen
                        los bimestres con asistencia registrada.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($chartData['asisJustificacion'])): ?>
            <div class="card cuadros-chart">
                <div class="card__header"><h3 class="card__title">Justificadas frente a sin justificar</h3></div>
                <div class="card__body">
                    <div id="chart-asis-justificacion"></div>
                    <p class="cuadros-nota">
                        Cuánto se justifica en cada nivel. Son contadores independientes:
                        una falta justificada no se descuenta de las faltas.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php // ── Asistencia · Comparativa por sección ───────────────── ?>
    <div id="panel-asis-secciones" class="tab-panel" role="tabpanel"
         data-panel="asis-secciones" aria-labelledby="tab-asis-secciones" hidden>
        <?php if (isset($chartData['asisFaltas']) || isset($chartData['asisTardanzas'])): ?>
            <?php if (isset($chartData['asisFaltas'])): ?>
            <div class="card cuadros-chart cuadros-chart--ancho mb-md">
                <div class="card__header"><h3 class="card__title">Faltas sin justificar por sección</h3></div>
                <div class="card__body">
                    <div id="chart-asis-faltas"></div>
                    <p class="cuadros-nota">
                        Secciones ordenadas de mayor a menor. (P) primaria, (S) secundaria.
                        Son totales de la sección, no promedios por estudiante.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($chartData['asisTardanzas'])): ?>
            <div class="card cuadros-chart cuadros-chart--ancho">
                <div class="card__header"><h3 class="card__title">Tardanzas sin justificar por sección</h3></div>
                <div class="card__body">
                    <div id="chart-asis-tardanzas"></div>
                    <p class="cuadros-nota">
                        Mismo criterio que el gráfico anterior, aplicado a las tardanzas.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="empty-state">Todavía no hay incidencias registradas en este bimestre.</p>
        <?php endif; ?>
    </div>

    <?php // ── Asistencia · Estudiantes con más faltas ────────────── ?>
    <div id="panel-asis-top" class="tab-panel" role="tabpanel"
         data-panel="asis-top" aria-labelledby="tab-asis-top" hidden>
        <?php if (empty($asisTop)): ?>
            <p class="empty-state">Ningún estudiante tiene faltas ni tardanzas sin justificar en este bimestre.</p>
        <?php else: ?>
            <div class="card">
                <div class="card__header"><h3 class="card__title">Por sección</h3></div>
                <div class="card__body">
                    <?php require VIEW_PATH . '/admin/cuadros/_top-incidencias.php'; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <p class="text-sm text-muted">
        El registro por estudiante está en
        <a href="<?= url('admin/asistencia') ?>">Asistencia</a>.
    </p>
</section>

<?php // ── Reaperturas del bimestre ────────────────────────────────── ?>
<?php if (!empty($bloques['reaperturas'])): ?>
    <section class="dash-grupo" aria-labelledby="cuadros-g-reaperturas">
        <h2 id="cuadros-g-reaperturas" class="dash-grupo__titulo">Reaperturas del bimestre</h2>
        <p class="text-sm text-muted">
            <?= count($bloques['reaperturas']) ?> reapertura(s) registrada(s). Quedan auditadas con su motivo.
        </p>
    </section>
<?php endif; ?>

<?php
// ── Datos y libreria de graficos ──────────────────────────────────────
// Sin JS inline: el JSON viaja en un tag y cuadros.js lo lee, igual que en
// /matriculas/resumen. JSON_HEX_TAG es OBLIGATORIO, no cosmetico: los
// nombres de seccion y de tutor los escribe el usuario y un "</script>"
// dentro de uno cerraria el bloque y romperia la pagina.
?>
<?php // tabs.js va FUERA del if y ANTES de cuadros.js. Fuera, porque las
      // pestanas tienen que funcionar aunque el bimestre no tenga ni un
      // grafico; antes, porque cuadros.js se apoya en su evento `tabs:mostrado`
      // para dibujar los graficos que nacen dentro de un panel oculto. ?>
<script src="<?= url('js/tabs.js') ?>"></script>
<?php if ($chartData): ?>
    <script type="application/json" id="cuadros-data"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <script src="<?= url('js/frappe-charts.min.js') ?>"></script>
    <script src="<?= url('js/cuadros.js') ?>"></script>
<?php endif; ?>

<?php endif; ?>
