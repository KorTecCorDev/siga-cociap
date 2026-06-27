<?php
/**
 * Formulario de reemplazo de docente en una carga activa.
 * @var array $carga     [id, docente..., area_nombre, subarea_nombre, grado_nombre, seccion_nombre, nivel_nombre, anio]
 * @var array $docentes  entrantes posibles (todos menos el actual)
 */
$salienteNombre = mb_strtoupper($carga['apellido_paterno'] . ' ' . $carga['apellido_materno'])
    . ', ' . ucwords(mb_strtolower($carga['docente_nombres']));
?>

<div class="page-header">
    <a href="<?= url('director/cargas/seccion/' . (int) $carga['seccion_id']) ?>"
       class="btn btn--secondary btn--sm">← Cargas de la sección</a>
    <div>
        <h1 class="page-title">Reemplazar docente</h1>
        <p class="page-subtitle">
            <?= e($carga['area_nombre']) ?><?= $carga['subarea_nombre'] ? ' · ' . e($carga['subarea_nombre']) : '' ?>
            — <?= e($carga['grado_nombre']) ?> <?= e($carga['seccion_nombre']) ?>
            · <?= e($carga['nivel_nombre']) ?> <?= e($carga['anio']) ?>
        </p>
    </div>
</div>

<div class="flash flash--warning">
    El docente entrante <strong>hereda</strong> los criterios, notas y conclusiones y
    continúa en vivo. El trabajo del docente saliente se <strong>archiva</strong> en un
    snapshot de solo lectura (todos los bimestres) para auditoría. La carga sigue activa;
    no se pierden datos. Esto no cambia la boleta ni el orden de mérito.
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="<?= url('director/cargas/' . (int) $carga['id'] . '/reemplazar') ?>">
            <?= csrf_field() ?>

            <div class="form-grid">
                <p class="form-section-title">Cambio</p>

                <div class="form-group">
                    <label class="form-label">Docente saliente (actual)</label>
                    <input type="text" class="form-input" value="<?= e($salienteNombre) ?>" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label" for="docente_entrante_id">
                        Docente entrante <span class="text-danger">*</span>
                    </label>
                    <select id="docente_entrante_id" name="docente_entrante_id" class="form-input" required>
                        <option value="">Seleccionar docente...</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= (int) $d['id'] ?>">
                                <?= e(mb_strtoupper($d['apellido_paterno'] . ' ' . $d['apellido_materno'])) ?>,
                                <?= e(ucwords(mb_strtolower($d['nombres']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="motivo">
                        Motivo del reemplazo <span class="text-danger">*</span>
                    </label>
                    <textarea id="motivo" name="motivo" class="form-input" rows="3" required
                              placeholder="Ej. renuncia, licencia, reasignación interna..."></textarea>
                </div>
            </div>

            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--primary"
                        onclick="return confirm('¿Confirmar el reemplazo de docente en esta carga?')">
                    Reemplazar y archivar
                </button>
                <a href="<?= url('director/cargas/seccion/' . (int) $carga['seccion_id']) ?>"
                   class="btn btn--secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
