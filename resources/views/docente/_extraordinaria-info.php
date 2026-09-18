<?php
/**
 * Card informativa de CALIFICACIONES EXTRAORDINARIAS de una competencia.
 * NO salen del registro ordinario del docente: las registró RA con
 * autorización (motivo abajo). Punto único de su marcado: lo incluyen el
 * resumen de competencia y la grilla del docente, donde la extraordinaria
 * ya NO se pinta como un criterio más (18/09/2026).
 *
 * @var array $extraordinarias filas de RectificacionModel::getExtraordinariasDeCompetencia
 */
if (empty($extraordinarias)) {
    return;
}
?>
<div class="extraordinaria-info">
    <p class="extraordinaria-info__titulo">
        Calificación extraordinaria — Registro Académico
    </p>
    <p class="extraordinaria-info__leyenda">
        Las siguientes calificaciones <strong>no forman parte de tu registro
        ordinario del bimestre</strong>: fueron ingresadas por Registro
        Académico con autorización, por el motivo registrado.
    </p>
    <ul class="extraordinaria-info__lista">
        <?php foreach ($extraordinarias as $ex): ?>
            <li class="extraordinaria-info__item">
                <strong><?= e($ex['estudiante']) ?></strong>
                — nota <?= fmt_nota((int) $ex['nota_nueva']) ?> ·
                <?= e(nota_a_literal((int) $ex['nota_nueva'])) ?>
                <span class="extraordinaria-info__meta">
                    Registrada por <?= e($ex['registrador'] ?: 'Registro Académico') ?>
                    el <?= e(fecha_es(substr((string) $ex['rectificado_en'], 0, 10))) ?>
                </span>
                <span class="extraordinaria-info__motivo">
                    Motivo: <?= e($ex['motivo']) ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
