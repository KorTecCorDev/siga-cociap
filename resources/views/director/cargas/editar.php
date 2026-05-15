<?php
/**
 * @var array  $carga
 * @var array  $sesionesMap   ['lunes' => ['hora_inicio'=>'07:45','hora_fin'=>'09:25'], ...]
 * @var array  $secciones
 * @var array  $docentes
 * @var array  $areas
 * @var array  $subareas
 * @var array  $dias
 */
?>

<div class="page-header">
    <a href="<?= url('director/cargas') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Editar Carga Académica</h1>
        <p class="page-subtitle">Modifica los datos de la carga seleccionada.</p>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="<?= url('director/cargas/' . $carga['id'] . '/editar') ?>" novalidate>
            <?= csrf_field() ?>

            <div class="form-grid">

                <p class="form-section-title">Asignación</p>

                <div class="form-group">
                    <label class="form-label" for="seccion_id">
                        Sección <span class="text-danger">*</span>
                    </label>
                    <select id="seccion_id" name="seccion_id" class="form-input" required>
                        <option value="">Seleccionar sección...</option>
                        <?php foreach ($secciones as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                    data-nivel-id="<?= $s['nivel_id'] ?>"
                                    data-anio-id="<?= $s['anio_id'] ?>"
                                    <?= (int)$s['id'] === (int)$carga['seccion_id'] ? 'selected' : '' ?>>
                                <?= e($s['grado']) ?> "<?= e($s['seccion']) ?>"
                                — <?= e($s['nivel']) ?> <?= e($s['anio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="docente_id">
                        Docente <span class="text-danger">*</span>
                    </label>
                    <select id="docente_id" name="docente_id" class="form-input" required>
                        <option value="">Seleccionar docente...</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                    <?= (int)$d['id'] === (int)$carga['docente_id'] ? 'selected' : '' ?>>
                                <?= e(mb_strtoupper($d['apellido_paterno'] . ' ' . $d['apellido_materno'])) ?>,
                                <?= e(ucwords(mb_strtolower($d['nombres']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="area_id">
                        Área <span class="text-danger">*</span>
                    </label>
                    <select id="area_id" name="area_id" class="form-input" required
                            data-selected="<?= (int)$carga['area_real_id'] ?>">
                        <option value="">Selecciona primero una sección...</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                    data-nivel-id="<?= $a['nivel_id'] ?>"
                                    data-tipo="<?= $a['tipo'] ?>"
                                    style="display:none">
                                <?= e($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="subarea-container" style="display:none">
                    <label class="form-label" for="subarea_id">
                        Subárea <span class="text-danger">*</span>
                    </label>
                    <select id="subarea_id" name="subarea_id" class="form-input" disabled
                            data-selected="<?= (int)($carga['subarea_id'] ?? 0) ?>">
                        <option value="">Seleccionar subárea...</option>
                        <?php foreach ($subareas as $sa): ?>
                            <option value="<?= $sa['id'] ?>" data-area-id="<?= $sa['area_id'] ?>" style="display:none">
                                <?= e($sa['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="form-section-title">Horario semanal</p>
                <p class="form-hint">Marca los días de clase e ingresa la hora de inicio y fin de cada sesión.</p>

            </div><!-- /.form-grid -->

            <div class="horario-grid">
                <?php foreach ($dias as $dia):
                    $tieneHorario = isset($sesionesMap[$dia]);
                    $hi = $tieneHorario ? $sesionesMap[$dia]['hora_inicio'] : '';
                    $hf = $tieneHorario ? $sesionesMap[$dia]['hora_fin']    : '';
                ?>
                <div class="dia-row <?= $tieneHorario ? 'dia-row--activo' : '' ?>" id="dia-row-<?= $dia ?>">
                    <div class="dia-row__check">
                        <input type="checkbox"
                               class="dia-check"
                               id="check_<?= $dia ?>"
                               name="dias_check[]"
                               value="<?= $dia ?>"
                               <?= $tieneHorario ? 'checked' : '' ?>>
                        <label for="check_<?= $dia ?>" class="dia-label">
                            <?= ucfirst($dia) ?>
                        </label>
                    </div>
                    <div class="dia-row__times">
                        <div class="form-group">
                            <label class="form-label" for="hi_<?= $dia ?>">Inicio</label>
                            <input type="time"
                                   id="hi_<?= $dia ?>"
                                   name="hora_inicio[<?= $dia ?>]"
                                   class="form-input form-input--time"
                                   value="<?= e($hi) ?>"
                                   <?= !$tieneHorario ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="hf_<?= $dia ?>">Fin</label>
                            <input type="time"
                                   id="hf_<?= $dia ?>"
                                   name="hora_fin[<?= $dia ?>]"
                                   class="form-input form-input--time"
                                   value="<?= e($hf) ?>"
                                   <?= !$tieneHorario ? 'disabled' : '' ?>>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--primary">Guardar cambios</button>
                <a href="<?= url('director/cargas') ?>" class="btn btn--secondary">Cancelar</a>
            </div>

        </form>
    </div>
</div>
