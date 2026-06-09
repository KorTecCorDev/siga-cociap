<?php
/**
 * Formulario de constancia de traslado de salida.
 *
 * @var array      $matricula
 * @var array      $motivos            valor => etiqueta
 * @var array      $periodos           [{id, numero, nombre_display}]
 * @var int        $periodoActivoId
 * @var int        $correlativoSugerido
 * @var int        $anio
 * @var array|null $responsable        vínculo responsable (prefill solicitante)
 * @var array      $tiposVinculo
 */
$mid = (int) $matricula['id'];

$parentescoPrefill = $responsable
    ? ($tiposVinculo[$responsable['tipo_vinculo']] ?? $responsable['tipo_vinculo'])
    : '';
?>

<div class="page-header">
    <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary btn--sm">← Ver matrícula</a>
    <div>
        <h1 class="page-title">Constancia de traslado</h1>
        <p class="page-subtitle">
            <?= e($matricula['nombre_completo']) ?> ·
            <?= e($matricula['grado_nombre'] ?? '—') ?> <?= e($matricula['seccion_nombre'] ?? '') ?> ·
            <?= e($matricula['nivel_nombre'] ?? '') ?>
        </p>
    </div>
</div>

<div class="retorno-aviso">
    <strong>⚠ Traslado de salida.</strong> Al registrar la constancia, la matrícula
    pasa a <em>trasladada</em> (sale del colegio): se desactiva el acceso del apoderado
    y sus boletas públicas, y el estudiante deja de figurar en calificaciones y orden de
    mérito. La acción queda registrada con un número de constancia oficial.
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="<?= url('matriculas/' . $mid . '/trasladar') ?>">
            <?= csrf_field() ?>

            <!-- ── Numeración ── -->
            <p class="form-section-title">Constancia</p>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="correlativo">N° de constancia <span class="text-danger">*</span></label>
                    <div class="constancia-numero">
                        <span class="constancia-numero__prefijo">N°</span>
                        <input type="number" id="correlativo" name="correlativo"
                               class="form-input constancia-numero__input"
                               min="1" value="<?= (int) $correlativoSugerido ?>" required>
                        <span class="constancia-numero__sufijo">-<?= (int) $anio ?>-CAVVG-DA</span>
                    </div>
                    <span class="text-sm text-muted">Sugerido: el siguiente al último emitido en el año. Editable.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fecha_constancia">Fecha de la constancia <span class="text-danger">*</span></label>
                    <input type="date" id="fecha_constancia" name="fecha_constancia" class="form-input"
                           value="<?= e(date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="periodo_id">Último bimestre cursado</label>
                    <select id="periodo_id" name="periodo_id" class="form-input">
                        <option value="">— No especificar —</option>
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $periodoActivoId ? 'selected' : '' ?>>
                                <?= e($p['nombre_display']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ── Colegio destino ── -->
            <p class="form-section-title">Institución educativa de destino</p>
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label class="form-label" for="ie_destino_nombre">Nombre de la IE destino <span class="text-danger">*</span></label>
                    <input type="text" id="ie_destino_nombre" name="ie_destino_nombre" class="form-input"
                           maxlength="200" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ie_destino_codigo_modular">Código modular <span class="text-danger">*</span></label>
                    <input type="text" id="ie_destino_codigo_modular" name="ie_destino_codigo_modular"
                           class="form-input" maxlength="30" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ie_destino_ugel">UGEL / DRE de destino</label>
                    <input type="text" id="ie_destino_ugel" name="ie_destino_ugel" class="form-input" maxlength="150">
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="ie_destino_ubicacion">Ubicación (distrito / provincia / departamento)</label>
                    <input type="text" id="ie_destino_ubicacion" name="ie_destino_ubicacion" class="form-input" maxlength="200">
                </div>
            </div>

            <!-- ── Motivo ── -->
            <p class="form-section-title">Motivo</p>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="motivo">Motivo del traslado <span class="text-danger">*</span></label>
                    <select id="motivo" name="motivo" class="form-input" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($motivos as $valor => $label): ?>
                            <option value="<?= e($valor) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="motivo_detalle">Detalle del motivo (opcional)</label>
                    <input type="text" id="motivo_detalle" name="motivo_detalle" class="form-input" maxlength="300">
                </div>
            </div>

            <!-- ── Solicitante ── -->
            <p class="form-section-title">Apoderado solicitante</p>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="solicitante_nombre">Nombre</label>
                    <input type="text" id="solicitante_nombre" name="solicitante_nombre" class="form-input"
                           maxlength="200" value="<?= e($responsable['nombre_completo'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="solicitante_dni">DNI</label>
                    <input type="text" id="solicitante_dni" name="solicitante_dni" class="form-input"
                           maxlength="8" value="<?= e($responsable['dni'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="solicitante_parentesco">Parentesco</label>
                    <input type="text" id="solicitante_parentesco" name="solicitante_parentesco" class="form-input"
                           maxlength="40" value="<?= e($parentescoPrefill) ?>">
                </div>
            </div>

            <!-- ── Situación / observaciones ── -->
            <p class="form-section-title">Situación y observaciones</p>
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label class="form-label" for="situacion_academica">Situación académica</label>
                    <input type="text" id="situacion_academica" name="situacion_academica" class="form-input"
                           maxlength="300" value="Matrícula vigente, sin deudas con la institución.">
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="observaciones">Observaciones (opcional)</label>
                    <textarea id="observaciones" name="observaciones" class="form-input" rows="2" maxlength="500"></textarea>
                </div>
            </div>

            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--danger"
                        onclick="return confirm('¿Registrar la constancia y trasladar la matrícula? Esta acción desactiva el acceso del apoderado.')">
                    Registrar y generar constancia
                </button>
                <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
