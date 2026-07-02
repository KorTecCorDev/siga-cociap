<?php
/**
 * @var array  $carga
 * @var array  $sesionesMap   ['lunes' => ['hora_inicio'=>'07:45','hora_fin'=>'09:25'], ...]
 * @var array  $secciones
 * @var array  $docentes
 * @var array  $areas
 * @var array  $subareas
 * @var array  $dias
 * @var array  $ocupadas      [seccion_id => ['areas'=>[...], 'subareas'=>[...]]]
 */
?>
<div id="cargasData"
     data-ocupadas="<?= e(json_encode($ocupadas)) ?>"
     data-bloques-docentes="<?= e(json_encode($bloquesDocentes)) ?>"
     data-bloques-seccion="<?= e(json_encode($bloquesSeccion)) ?>"
     data-hora-inicio="<?= e(json_encode($horaInicioClases)) ?>"
     hidden></div>

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
                    <p class="form-hint">
                        Para <strong>cambiar el docente</strong> de esta carga usa el
                        <a href="<?= url('director/cargas/' . $carga['id'] . '/reemplazar') ?>">proceso de Reemplazo de docente</a>:
                        conserva la auditoria del trabajo del saliente. Editar el docente aqui sera rechazado.
                    </p>
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

                <?php // Carga sin sesiones = registrada "sin horario propio". ?>
                <label class="sin-horario-check" for="sin_horario">
                    <input type="checkbox" id="sin_horario" name="sin_horario" value="1"
                           <?= empty($sesionesMap) ? 'checked' : '' ?>>
                    <span class="sin-horario-check__texto">
                        <strong>Sin horario propio</strong>
                        <small>La carga se dicta dentro del horario de otra carga del área
                        (subáreas con el mismo docente) o su horario aún no se registra.</small>
                    </span>
                </label>

                <p class="form-hint">Marca los días de clase e ingresa la hora de inicio y fin de cada sesión.</p>

            </div><!-- /.form-grid -->

            <div class="horario-grid">
                <?php foreach ($dias as $dia):
                    $rangos       = $sesionesMap[$dia] ?? [];
                    $tieneHorario = !empty($rangos);
                    // Día sin clases: igual se pinta un rango vacío (deshabilitado)
                    // para que exista la plantilla a clonar y se active al marcar.
                    if (!$tieneHorario) {
                        $rangos = [['hora_inicio' => '', 'hora_fin' => '']];
                    }
                    $varios = count($rangos) > 1;
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
                    <div class="dia-row__bloques" id="bloques-<?= $dia ?>">
                        <?php foreach ($rangos as $r): ?>
                        <div class="bloque-rango">
                            <div class="form-group">
                                <label class="form-label">Inicio</label>
                                <input type="time"
                                       name="hora_inicio[<?= $dia ?>][]"
                                       class="form-input form-input--time bloque-inicio"
                                       value="<?= e($r['hora_inicio']) ?>"
                                       <?= !$tieneHorario ? 'disabled' : '' ?>>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fin</label>
                                <input type="time"
                                       name="hora_fin[<?= $dia ?>][]"
                                       class="form-input form-input--time bloque-fin"
                                       value="<?= e($r['hora_fin']) ?>"
                                       <?= !$tieneHorario ? 'disabled' : '' ?>>
                            </div>
                            <button type="button" class="btn btn--danger btn--sm bloque-quitar"
                                    title="Quitar bloque" tabindex="-1" <?= $varios ? '' : 'hidden' ?>>&times;</button>
                        </div>
                        <?php endforeach; ?>
                        <small id="hint-<?= $dia ?>" class="dia-row__hint" hidden></small>
                        <span class="dia-row__ayuda" id="ayuda-<?= $dia ?>" hidden>
                            <button type="button" class="dia-row__ayuda-btn"
                                    aria-label="Ver bloques libres del <?= ucfirst($dia) ?>"></button>
                            <span class="dia-row__ayuda-tip" id="ayuda-tip-<?= $dia ?>" role="tooltip"></span>
                        </span>
                        <button type="button" class="btn btn--secondary btn--sm bloque-agregar"
                                data-dia="<?= $dia ?>" <?= !$tieneHorario ? 'disabled' : '' ?>>+ Agregar bloque</button>
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
