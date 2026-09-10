<?php
/**
 * Redactar un COMUNICADO a docentes (migración 058).
 *
 * Solo admin / registro académico llegan aquí: los tres roles de dirección son
 * SOLO LECTURA y entran a su bandeja, pero no redactan. El gate real está en el
 * controlador, por método.
 *
 * @var array $secciones filas de SeccionModel::listarConTutor()
 */
?>

<div class="page-header">
    <a href="<?= url('notificaciones') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Nuevo comunicado</h1>
        <p class="page-subtitle">
            Llega a la bandeja de cada destinatario y a la campana de su barra superior.
        </p>
    </div>
</div>

<form method="POST" action="<?= url('notificaciones/comunicado') ?>" class="card">
    <div class="card__body">
        <?= csrf_field() ?>

        <p class="form-section-title">Destinatarios <span class="text-danger">*</span></p>
        <div class="form-group">
            <label class="notif-opcion">
                <input type="radio" name="destino" value="docentes" checked>
                <span>
                    <strong>Todos los docentes</strong>
                    <span class="text-sm text-muted">Llega a cada docente activo del colegio.</span>
                </span>
            </label>
            <label class="notif-opcion">
                <input type="radio" name="destino" value="seccion">
                <span>
                    <strong>Docentes de una sección</strong>
                    <span class="text-sm text-muted">Solo quienes tienen carga activa en esa sección.</span>
                </span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label" for="seccion_id">Sección</label>
            <select id="seccion_id" name="seccion_id" class="form-input">
                <option value="">—</option>
                <?php foreach ($secciones as $s): ?>
                    <option value="<?= (int) $s['id'] ?>">
                        <?= e($s['nivel_nombre']) ?> ·
                        <?= e($s['grado_nombre']) ?> "<?= e($s['seccion_nombre']) ?>"
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-sm text-muted">Solo se usa si elegiste "Docentes de una sección".</p>
        </div>

        <p class="form-section-title">Mensaje</p>
        <div class="form-group">
            <label class="form-label" for="titulo">Título <span class="text-danger">*</span></label>
            <input type="text" id="titulo" name="titulo" class="form-input"
                   maxlength="150" required
                   placeholder="P. ej. Cierre del III Bimestre">
        </div>
        <div class="form-group">
            <label class="form-label" for="mensaje">Mensaje <span class="text-danger">*</span></label>
            <textarea id="mensaje" name="mensaje" class="form-input" rows="6" required
                      placeholder="Escribe aqui el comunicado."></textarea>
        </div>

        <div class="btn-group form-actions">
            <a href="<?= url('notificaciones') ?>" class="btn btn--secondary">Cancelar</a>
            <button type="submit" class="btn btn--primary">Enviar comunicado</button>
        </div>
    </div>
</form>
