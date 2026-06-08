<?php
/**
 * Vista: Resolver empate irreducible del orden de mérito.
 *
 * @var array $periodo  { id, anio, nombre_display }
 * @var array $grado    { id, nombre_display, nivel_nombre }
 * @var array $grupos   [empate_clave => [ {matricula_id, apellido_*, nombres, seccion_nombre,
 *                                          num_competencias, promedio_general, num_c, num_b, num_ad} ]]
 */
?>

<div class="page-header">
    <a href="<?= url('director/orden-merito/' . $periodo['id']) ?>"
       class="btn btn--secondary btn--sm">← Volver al ranking</a>
    <div>
        <h1 class="page-title">Resolver empate</h1>
        <p class="page-subtitle">
            <?= e($grado['nivel_nombre']) ?> — <?= e($grado['nombre_display']) ?> ·
            <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?>
        </p>
    </div>
</div>

<?php if (empty($grupos)): ?>
    <div class="empty-state">
        <p>No hay empates por resolver en este grado.</p>
    </div>
<?php else: ?>

    <div class="card mb-lg">
        <div class="card__body">
            <p class="text-muted">
                Estos alumnos tienen el mismo promedio y una distribución de calificaciones
                que la cascada automática no puede separar (o un número de competencias
                distinto por exoneración). Asigna el orden del puesto en disputa
                (1 = puesto superior) e indica el motivo. La decisión queda registrada.
            </p>
        </div>
    </div>

    <?php foreach ($grupos as $clave => $alumnos): ?>
        <form method="POST"
              action="<?= url('director/orden-merito/' . $periodo['id'] . '/desempate/' . $grado['id']) ?>"
              class="card mb-lg">
            <?= csrf_field() ?>

            <div class="card__header">
                <h2 class="card__title">Grupo empatado (<?= count($alumnos) ?> alumnos)</h2>
            </div>

            <div class="card__body">
                <table class="tabla-ranking">
                    <thead>
                        <tr>
                            <th class="text-center">Orden</th>
                            <th>Apellidos y nombres</th>
                            <th class="text-center">Sección</th>
                            <th class="text-center">Comp.</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-center">AD / B / C</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($alumnos as $a): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="number"
                                           name="orden[<?= (int) $a['matricula_id'] ?>]"
                                           class="input-orden"
                                           min="1" max="<?= count($alumnos) ?>"
                                           value="<?= $i ?>" required>
                                </td>
                                <td>
                                    <?= e($a['apellido_paterno'] . ' ' .
                                        $a['apellido_materno'] . ', ' . $a['nombres']) ?>
                                </td>
                                <td class="text-center"><?= e($a['seccion_nombre']) ?></td>
                                <td class="text-center"><?= (int) $a['num_competencias'] ?></td>
                                <td class="text-center">
                                    <strong><?= sprintf('%05.2f', $a['promedio_general']) ?></strong>
                                </td>
                                <td class="text-center">
                                    <?= (int) $a['num_ad'] ?> / <?= (int) $a['num_b'] ?> / <?= (int) $a['num_c'] ?>
                                </td>
                            </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>

                <div class="form-group mt-md">
                    <label class="form-label" for="motivo-<?= e($clave) ?>">
                        Motivo de la decisión <span class="text-danger">*</span>
                    </label>
                    <textarea id="motivo-<?= e($clave) ?>" name="motivo"
                              class="form-control" rows="2" required
                              placeholder="Ej.: a igual rendimiento, se prioriza por acuerdo del comité directivo del..."></textarea>
                </div>
            </div>

            <div class="card__footer form-actions">
                <button type="submit" class="btn btn--primary">Guardar resolución</button>
            </div>
        </form>
    <?php endforeach; ?>

<?php endif; ?>
