<?php
/**
 * CALIFICACIÓN EXTRAORDINARIA EN LOTE — todas las competencias sin nota del
 * alumno en UN bimestre, en una sola pantalla y con un motivo común.
 *
 * Es la MISMA alta que rectificaciones/extraordinaria.php (mismo motor, mismas
 * guardas, misma auditoría): lo único que cambia es la captura. Un alumno
 * matriculado después del cierre necesita 25-27 altas, y de una en una eran
 * 25-27 pasadas por el formulario.
 *
 * @var array $info                datos del estudiante (incl. nivel_codigo, matricula_id)
 * @var array $periodo             ['id','nombre','estado']
 * @var array $porArea             [{area_id, area_nombre, items[]}]
 * @var int   $total               competencias insertables del bimestre
 * @var array $literalesConclusion literales que EXIGEN conclusión en este nivel
 */
$volver     = url('rectificaciones/matricula/' . (int) $info['matricula_id']);

/** Etiqueta de competencia: antepone la subárea en áreas con subáreas. */
$labelComp = static function (array $c): string {
    $nombre = $c['nombre_corto'] ?: $c['competencia_nombre'];
    if (($c['area_tipo'] ?? '') === 'con_subareas' && !empty($c['subarea_nombre'])) {
        return $c['subarea_nombre'] . ' — ' . $nombre;
    }
    return $nombre;
};

$obligatoriaTxt = $literalesConclusion === []
    ? 'En este nivel no se exige conclusión descriptiva.'
    : 'Obligatoria cuando el resultado sea ' . implode(' o ', $literalesConclusion) . '.';
?>

<div class="page-header">
    <a href="<?= $volver ?>" class="btn btn--secondary btn--sm">← Cancelar</a>
    <div>
        <h1 class="page-title">Calificación extraordinaria en lote</h1>
        <p class="page-subtitle">
            <?= e($info['nombre_completo']) ?> ·
            <?= e($info['grado_nombre']) ?> "<?= e($info['seccion_nombre']) ?>" ·
            <?= e($periodo['nombre']) ?>
            <?php if ($periodo['estado'] === 'cerrado'): ?>
                <span class="rect-chip rect-chip--cerrado">Cerrado</span>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="card mb-md">
    <div class="card__body">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-item__label">Estudiante</span>
                <span class="info-item__value"><?= e($info['nombre_completo']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-item__label">DNI</span>
                <span class="info-item__value"><?= e($info['dni']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-item__label">Sección</span>
                <span class="info-item__value">
                    <?= e($info['nivel_nombre']) ?> ·
                    <?= e($info['grado_nombre']) ?> "<?= e($info['seccion_nombre']) ?>"
                </span>
            </div>
            <div class="info-item">
                <span class="info-item__label">Competencias sin nota</span>
                <span class="info-item__value"><?= (int) $total ?> en <?= e($periodo['nombre']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="flash flash--warning">
    Las calificaciones extraordinarias se registran en un criterio único a nombre de
    Registro Académico, <strong>separado del registro ordinario del docente</strong>.
    Aparecen en la boleta del estudiante y se exportan al SIAGIE, pero
    <strong>no cuentan para el orden de mérito</strong>. Si el bimestre está
    cerrado, la familia las verá en la boleta apenas las registres.
</div>

<form method="POST"
      action="<?= url('rectificaciones/extraordinaria/lote/guardar') ?>"
      id="rectLoteForm"
      class="rect-lote"
      data-nota-min-ad="<?= NOTA_MIN_AD ?>"
      data-nota-min-a="<?= NOTA_MIN_A ?>"
      data-nota-min-b="<?= NOTA_MIN_B ?>"
      data-literales-conclusion="<?= e(implode(',', $literalesConclusion)) ?>"
      data-total="<?= (int) $total ?>">

    <?= csrf_field() ?>
    <input type="hidden" name="matricula_id" value="<?= (int) $info['matricula_id'] ?>">
    <input type="hidden" name="periodo_id"   value="<?= (int) $periodo['id'] ?>">

    <div class="card mb-md">
        <div class="card__body">
            <p class="form-section-title">Motivo del lote <span class="text-danger">*</span></p>
            <p class="rect-aviso mb-md">
                Se guarda en la auditoría de <strong>cada</strong> nota de este lote, y el
                docente lo verá junto a la calificación en sus vistas de solo lectura.
            </p>
            <div class="form-group">
                <textarea id="motivo" name="motivo" class="form-input" rows="3" required
                          placeholder="Fundamenta la autorizacion (p. ej. estudiante matriculado el 13/07; notas tomadas del registro fisico del docente)."></textarea>
            </div>
        </div>
    </div>

    <?php foreach ($porArea as $area): ?>
    <div class="card mb-md">
        <div class="card__body">
            <p class="form-section-title"><?= e($area['area_nombre']) ?></p>
            <div class="tabla-notas-wrapper">
                <table class="tabla-notas rect-lote__tabla">
                    <thead>
                        <tr>
                            <th>Competencia</th>
                            <th class="text-center">Situación</th>
                            <th class="text-center">Nota (0-20)</th>
                            <th class="text-center">Literal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($area['items'] as $c):
                            $clave        = (int) $c['carga_id'] . '-' . (int) $c['competencia_id'];
                            $noTrabajada  = (int) $c['notas_seccion'] === 0;
                            $sinBloqueo   = (int) $c['bloqueada'] === 0;
                            $esTransversal = !empty($c['es_transversal']);
                            $idNota       = 'nota_' . str_replace('-', '_', $clave);
                        ?>
                        <tr class="rect-lote__fila" data-clave="<?= e($clave) ?>">
                            <td>
                                <div class="rect-comp__nombre">
                                    <?= e($labelComp($c)) ?>
                                    <?php if ($esTransversal): ?>
                                        <span class="rect-chip rect-chip--transversal">Transversal</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($esTransversal): ?>
                                    <div class="rect-lote__fila-pie">
                                        Se registra en la carga que ya evalúa esta transversal en la sección,
                                        y la conclusión va al registro del tutor.
                                    </div>
                                <?php endif; ?>
                                <?php if ($sinBloqueo): ?>
                                    <div class="rect-lote__aviso">
                                        La competencia no está bloqueada: la nota quedará registrada,
                                        pero no saldrá en la boleta hasta que el docente la bloquee.
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-sm">
                                <?php if ($noTrabajada): ?>
                                    No trabajada por el docente
                                <?php else: ?>
                                    Sin nota individual
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <input type="number"
                                       id="<?= e($idNota) ?>"
                                       aria-label="Nota de <?= e($labelComp($c)) ?>"
                                       name="nota[<?= e($clave) ?>]"
                                       class="form-input rect-nota-input rect-lote__nota"
                                       min="0" max="20" step="1" inputmode="numeric"
                                       autocomplete="off">
                            </td>
                            <td class="text-center">
                                <span class="rect-lote__literal" data-literal="">—</span>
                            </td>
                        </tr>
                        <tr class="rect-lote__conclusion" data-clave="<?= e($clave) ?>" hidden>
                            <td colspan="4">
                                <label class="form-label" for="concl_<?= e(str_replace('-', '_', $clave)) ?>">
                                    Conclusión descriptiva de <?= e($labelComp($c)) ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea id="concl_<?= e(str_replace('-', '_', $clave)) ?>"
                                          name="conclusion[<?= e($clave) ?>]"
                                          class="form-input" rows="2"
                                          placeholder="Describe el nivel de logro alcanzado."></textarea>
                                <p class="text-sm text-muted"><?= e($obligatoriaTxt) ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="rect-lote__barra">
        <div class="rect-lote__contador">
            <strong data-rect-lote-llenas>0</strong> de <?= (int) $total ?> competencias con nota.
            <span class="text-muted">Las que dejes vacías no se registran.</span>
        </div>
        <div class="btn-group">
            <a href="<?= $volver ?>" class="btn btn--secondary">Cancelar</a>
            <button type="submit" class="btn btn--primary" data-rect-lote-submit disabled>
                Registrar calificaciones
            </button>
        </div>
    </div>
</form>
