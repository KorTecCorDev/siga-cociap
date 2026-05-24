<?php
/**
 * @var array  $seccion        { id, seccion_nombre, grado_nombre, nivel_nombre }
 * @var array  $anio           { id, anio }
 * @var array  $exoneraciones  [{ id, alumno_nombre, area_nombre, subarea_nombre, motivo, registrado_en, registrado_por_nombre }]
 * @var array  $alumnos        [{ matricula_id, nombre_completo }]
 * @var array  $opciones       [{ value, label }]
 */
?>

<div class="page-header">
    <a href="<?= url('admin/exoneraciones') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            Exoneraciones — <?= e($seccion['grado_nombre']) ?> — Sección <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle">Año Académico <?= e($anio['anio']) ?></p>
    </div>
</div>

<!-- ── Lista de exoneraciones activas ────────────────────── -->
<div class="card mb-lg">
    <div class="card__header">
        <h2 class="card__title">Exoneraciones activas</h2>
    </div>

    <?php if (empty($exoneraciones)): ?>
        <div class="card__body">
            <p class="text-muted">No hay exoneraciones registradas para esta sección.</p>
        </div>
    <?php else: ?>
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Área / Subárea</th>
                    <th>Motivo</th>
                    <th>Registrado por</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exoneraciones as $e): ?>
                    <tr>
                        <td><strong><?= e($e['alumno_nombre']) ?></strong></td>
                        <td>
                            <?php if ($e['subarea_id']): ?>
                                <?= e($e['area_nombre']) ?> &rsaquo; <strong><?= e($e['subarea_nombre']) ?></strong>
                                <small class="text-muted">(subárea)</small>
                            <?php else: ?>
                                <strong><?= e($e['area_nombre']) ?></strong>
                                <small class="text-muted">(área completa)</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= e($e['motivo']) ?></td>
                        <td class="text-muted text-sm"><?= e($e['registrado_por_nombre']) ?></td>
                        <td class="text-muted text-sm">
                            <?= date('d/m/Y', strtotime($e['registrado_en'])) ?>
                        </td>
                        <td>
                            <form method="POST"
                                  action="<?= url('admin/exoneraciones/' . (int)$e['id'] . '/revocar') ?>"
                                  onsubmit="return confirm('¿Revocar esta exoneración? El alumno volverá a ser evaluado normalmente.')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--danger btn--sm">Revocar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ── Formulario nueva exoneración ─────────────────────── -->
<?php if (empty($alumnos)): ?>
    <div class="flash flash--info">
        No hay alumnos matriculados en esta sección.
    </div>
<?php elseif (empty($opciones)): ?>
    <div class="flash flash--info">
        No hay áreas configuradas para esta sección (sin cargas activas).
    </div>
<?php else: ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Registrar nueva exoneración</h2>
    </div>
    <div class="card__body">
        <form method="POST"
              action="<?= url('admin/exoneraciones/' . $seccion['id'] . '/registrar') ?>"
              class="form-grid form-grid--2">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="matricula_id">Alumno *</label>
                <select id="matricula_id" name="matricula_id" class="form-input" required>
                    <option value="">— Selecciona alumno —</option>
                    <?php foreach ($alumnos as $a): ?>
                        <option value="<?= $a['matricula_id'] ?>"><?= e($a['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="area_subarea">Área / Subárea exonerada *</label>
                <select id="area_subarea" name="area_subarea" class="form-input" required>
                    <option value="">— Selecciona área o subárea —</option>
                    <?php foreach ($opciones as $op): ?>
                        <option value="<?= e($op['value']) ?>"><?= e($op['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group form-group--full">
                <label class="form-label" for="motivo">Motivo</label>
                <input type="text" id="motivo" name="motivo"
                       class="form-input"
                       maxlength="255"
                       placeholder="Ej: Razones de salud, creencias religiosas, etc.">
            </div>

            <div class="form-actions form-actions--full">
                <button type="submit" class="btn btn--primary">
                    Registrar exoneración
                </button>
                <a href="<?= url('admin/exoneraciones') ?>" class="btn btn--secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
