<?php
/**
 * NOTAS DEL COLEGIO DE ORIGEN — captura en lote.
 *
 * El Informe de Progreso del colegio anterior llega como un documento entero,
 * así que se transcribe entero: el formulario nace con varias filas y se pueden
 * añadir más. Las filas en blanco se descartan sin error.
 *
 * ⚠️ Estas notas NO van a la boleta del COCIAP (regla del colegio): son
 * informativas para los docentes con carga en la sección del estudiante. El
 * caso contrario —notas NUESTRAS que no se registraron a tiempo— va por la
 * calificación extraordinaria, desde /rectificaciones.
 *
 * @var array $matricula
 * @var array $notas  ya registradas
 * @var array $areas  áreas del plan de SU sección, para el mapeo opcional
 */
$mid = (int) $matricula['id'];

/** Filas en blanco con las que nace el formulario. */
$filasIniciales = 6;
?>

<div class="page-header">
    <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary btn--sm">← Ver matrícula</a>
    <div>
        <h1 class="page-title">Notas del colegio de origen</h1>
        <p class="page-subtitle"><?= e($matricula['nombre_completo']) ?></p>
    </div>
</div>

<div class="flash flash--warning">
    Estas calificaciones <strong>no aparecen en la boleta del COCIAP</strong> ni cuentan
    para el orden de mérito: el Informe de Progreso que emite el colegio lleva solo lo
    cursado aquí. Sirven para que los <strong>docentes con carga en su sección</strong>
    sepan con qué llega el estudiante, y se les avisa al guardarlas.
    <br>
    Si lo que necesitas es registrar notas que <strong>sí se evaluaron en el COCIAP</strong>
    y no llegaron a ingresarse, eso va por
    <a href="<?= url('rectificaciones/matricula/' . $mid) ?>">Rectificar o completar calificaciones</a>.
</div>

<form method="POST" action="<?= url('matriculas/' . $mid . '/notas-externas') ?>"
      id="notasOrigenForm" class="notas-origen">
    <?= csrf_field() ?>

    <div class="card mb-md">
        <div class="card__body">
            <div class="form-group">
                <label class="form-label" for="colegio_origen">Colegio de origen</label>
                <input type="text" id="colegio_origen" name="colegio_origen" class="form-input"
                       maxlength="200" value="<?= e($notas[0]['colegio_origen'] ?? '') ?>"
                       placeholder="Nombre de la institución educativa anterior">
                <p class="text-sm text-muted">Se aplica a todas las filas de este envío.</p>
            </div>
        </div>
    </div>

    <div class="card mb-md">
        <div class="card__body">
            <p class="form-section-title">Calificaciones del informe de origen</p>
            <div class="tabla-notas-wrapper">
                <table class="tabla-notas notas-origen__tabla">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Área (como la llama el otro colegio)</th>
                            <th>Competencia</th>
                            <th>Nota</th>
                            <th>Área de nuestro plan</th>
                        </tr>
                    </thead>
                    <tbody data-notas-origen-cuerpo>
                        <?php for ($i = 0; $i < $filasIniciales; $i++): ?>
                        <tr class="notas-origen__fila">
                            <td>
                                <input type="text" name="periodo_nombre[]" class="form-input"
                                       maxlength="30" placeholder="I Bimestre"
                                       aria-label="Periodo">
                            </td>
                            <td>
                                <input type="text" name="area_nombre[]" class="form-input"
                                       maxlength="120" placeholder="Matemática"
                                       aria-label="Área del colegio de origen">
                            </td>
                            <td>
                                <input type="text" name="competencia_nombre[]" class="form-input"
                                       maxlength="120" placeholder="Resuelve problemas de cantidad"
                                       aria-label="Competencia">
                            </td>
                            <td>
                                <select name="nota_literal[]" class="form-input" aria-label="Nota literal">
                                    <option value="">—</option>
                                    <option value="AD">AD</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </td>
                            <td>
                                <select name="area_id[]" class="form-input" aria-label="Área de nuestro plan">
                                    <option value="">Sin mapear</option>
                                    <?php foreach ($areas as $a): ?>
                                        <option value="<?= (int) $a['id'] ?>"><?= e($a['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <p class="text-sm text-muted mt-md">
                El <strong>área de nuestro plan</strong> es opcional: solo sirve para
                resaltarle la fila al docente que dicta esa área. Si el otro colegio
                trabaja algo sin equivalente aquí, déjalo sin mapear.
            </p>

            <div class="btn-group">
                <button type="button" class="btn btn--secondary btn--sm" data-notas-origen-agregar>
                    + Añadir fila
                </button>
            </div>
        </div>
    </div>

    <div class="btn-group form-actions">
        <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary">Cancelar</a>
        <button type="submit" class="btn btn--primary">Guardar notas de origen</button>
    </div>
</form>

<div class="card">
    <div class="card__body">
        <p class="form-section-title">Ya registradas (<?= count($notas) ?>)</p>
        <?php if (empty($notas)): ?>
            <div class="empty-state"><p>Todavía no hay notas del colegio de origen.</p></div>
        <?php else: ?>
            <div class="tabla-notas-wrapper">
                <table class="tabla-notas">
                    <thead>
                        <tr>
                            <th>Periodo</th><th>Área (origen)</th><th>Competencia</th>
                            <th class="text-center">Nota</th><th>Área nuestra</th><th>Colegio origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notas as $n): ?>
                        <tr>
                            <td class="text-sm"><?= e($n['periodo_nombre']) ?></td>
                            <td class="text-sm"><?= e($n['area_nombre']) ?></td>
                            <td class="text-sm"><?= e($n['competencia_nombre']) ?></td>
                            <td class="text-center"><span class="matricula-badge matricula-badge--nuevo"><?= e($n['nota_literal']) ?></span></td>
                            <td class="text-sm text-muted"><?= e($n['area_mapeada_boleta'] ?: $n['area_mapeada'] ?: 'Sin mapear') ?></td>
                            <td class="text-sm text-muted"><?= e($n['colegio_origen'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-muted mt-md">
                Volver a guardar la misma competencia y periodo <strong>reemplaza</strong> la
                nota anterior; no se duplica.
            </p>
        <?php endif; ?>
    </div>
</div>
