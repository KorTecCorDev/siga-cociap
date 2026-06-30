<?php
/**
 * @var array  $secciones
 * @var array  $docentes
 * @var array  $areas
 * @var array  $subareas
 * @var array  $dias
 * @var array  $ocupadas         [seccion_id => ['areas'=>[...], 'subareas'=>[...]]]
 * @var int    $preselSeccionId  ID de la sección pre-seleccionada (0 = ninguna)
 * @var int    $preselDocenteId  ID del docente pre-seleccionado (0 = ninguno; solo si sección es unidocente)
 */
?>
<div id="cargasData"
     data-ocupadas="<?= e(json_encode($ocupadas)) ?>"
     data-horarios="<?= e(json_encode($horarios)) ?>"
     data-bloques-docentes="<?= e(json_encode($bloquesDocentes)) ?>"
     hidden></div>

<div class="page-header">
    <a href="<?= url('director/cargas') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Nueva Carga Académica</h1>
        <p class="page-subtitle">Asigna un docente a un área o subárea en una sección.</p>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="<?= url('director/cargas/crear') ?>" novalidate>
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
                                    data-es-unidocente="<?= $s['es_unidocente'] ? '1' : '0' ?>"
                                    data-tutor-id="<?= (int) $s['tutor_id'] ?>"
                                    <?= ($preselSeccionId && $s['id'] == $preselSeccionId) ? 'selected' : '' ?>>
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
                                    <?= ($preselDocenteId && $d['id'] == $preselDocenteId) ? 'selected' : '' ?>>
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
                    <select id="area_id" name="area_id" class="form-input" required>
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
                    <select id="subarea_id" name="subarea_id" class="form-input" disabled>
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
                <?php foreach ($dias as $dia): ?>
                <div class="dia-row" id="dia-row-<?= $dia ?>">
                    <div class="dia-row__check">
                        <input type="checkbox"
                               class="dia-check"
                               id="check_<?= $dia ?>"
                               name="dias_check[]"
                               value="<?= $dia ?>">
                        <label for="check_<?= $dia ?>" class="dia-label">
                            <?= ucfirst($dia) ?>
                        </label>
                    </div>
                    <div class="dia-row__bloques" id="bloques-<?= $dia ?>">
                        <div class="bloque-rango">
                            <div class="form-group">
                                <label class="form-label">Inicio</label>
                                <input type="time"
                                       name="hora_inicio[<?= $dia ?>][]"
                                       class="form-input form-input--time bloque-inicio"
                                       disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fin</label>
                                <input type="time"
                                       name="hora_fin[<?= $dia ?>][]"
                                       class="form-input form-input--time bloque-fin"
                                       disabled>
                            </div>
                            <button type="button" class="btn btn--danger btn--sm bloque-quitar"
                                    title="Quitar bloque" tabindex="-1" hidden>&times;</button>
                        </div>
                        <small id="hint-<?= $dia ?>" class="dia-row__hint" hidden></small>
                        <button type="button" class="btn btn--secondary btn--sm bloque-agregar"
                                data-dia="<?= $dia ?>" disabled>+ Agregar bloque</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--primary">Registrar carga</button>
                <a href="<?= url('director/cargas') ?>" class="btn btn--secondary">Cancelar</a>
            </div>

        </form>
    </div>
</div>
