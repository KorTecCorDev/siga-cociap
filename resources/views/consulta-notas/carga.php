<?php
/**
 * Consulta de calificaciones — grillas criterio-a-criterio (solo lectura) de
 * una carga. Reutiliza el mismo lenguaje visual del resumen del docente vía el
 * parcial _tabla.php; aqui NO hay inputs ni botones (solo lo oficial).
 * @var array $periodo
 * @var array $carga         [id, grado_nombre, seccion_nombre, nivel_nombre, nivel_codigo, area_nombre, subarea_nombre, docente]
 * @var array $competencias  [{competencia, criterios, alumnos, bloqueado_en}]
 * @var array $exonerados    matricula_ids exonerados de la carga
 */
$area = $carga['subarea_nombre']
    ? $carga['area_nombre'] . ' — ' . $carga['subarea_nombre']
    : $carga['area_nombre'];
$nivelCodigo = $carga['nivel_codigo'];
?>

<div class="page-header">
    <a href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/seccion/' . (int) $carga['seccion_id']) ?>"
       class="btn btn--secondary btn--sm">← Áreas</a>
    <div>
        <h1 class="page-title"><?= e($area) ?></h1>
        <p class="page-subtitle">
            <?= e($carga['grado_nombre'] . ' ' . $carga['seccion_nombre']) ?> ·
            <?= e($carga['nivel_nombre']) ?> ·
            <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?>
            — Docente: <?= e($carga['docente'] ?: 'Sin docente') ?>
        </p>
    </div>
    <span class="badge badge--activo">Solo lectura</span>
</div>

<?php foreach ($competencias as $bloque): ?>
    <?php
    $competencia     = $bloque['competencia'];
    $criterios       = $bloque['criterios'];
    $alumnos         = $bloque['alumnos'];
    $extraordinarias = $bloque['extraordinarias'] ?? [];
    ?>
    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title"><?= e($competencia['nombre_completo']) ?></h2>
            <span class="competencia-card__codigo"><?= e($competencia['codigo_minedu'] ?? '') ?></span>
        </div>
        <?php require VIEW_PATH . '/consulta-notas/_tabla.php'; ?>
    </div>
<?php endforeach; ?>
