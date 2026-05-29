<?php
/**
 * Cards de rendimiento por grado (1.º puesto + 2 de menor rendimiento).
 * Reutilizado por el modal de cierre (show.php) y por la página de
 * indicadores (stats.php). Espera la variable $porGrado.
 *
 * @var array $porGrado
 */
$porGrado = $porGrado ?? [];
?>
<section class="stats-bloque">
    <h3 class="stats-bloque__titulo">Rendimiento por grado</h3>

    <?php if (empty($porGrado)): ?>
        <p class="text-muted text-sm">Aún no hay calificaciones registradas en este bimestre.</p>
    <?php else: ?>
        <div class="stats-grados">
            <?php foreach ($porGrado as $g): ?>
            <div class="stats-grado">
                <div class="stats-grado__head">
                    <span class="stats-grado__nombre"><?= e($g['grado']['nombre_display']) ?></span>
                    <span class="stats-grado__nivel"><?= e($g['grado']['nivel_nombre']) ?></span>
                </div>

                <div class="stats-grado__mejor">
                    <span class="stats-tag stats-tag--mejor">1.&deg; puesto</span>
                    <div class="stats-est">
                        <span class="stats-est__nombre"><?= e($g['mejor']['nombre_completo']) ?></span>
                        <span class="stats-est__meta">
                            Sec. <?= e($g['mejor']['seccion_nombre']) ?>
                            &middot; promedio <strong><?= e(number_format((float) $g['mejor']['promedio_general'], 2)) ?></strong>
                        </span>
                    </div>
                </div>

                <?php if (!empty($g['peores'])): ?>
                <div class="stats-grado__peores">
                    <span class="stats-tag stats-tag--peor">Menor rendimiento</span>
                    <ul class="stats-est-lista">
                        <?php foreach ($g['peores'] as $peor): ?>
                        <li class="stats-est">
                            <span class="stats-est__nombre"><?= e($peor['nombre_completo']) ?></span>
                            <span class="stats-est__meta">
                                Sec. <?= e($peor['seccion_nombre']) ?>
                                &middot; puesto <?= (int) $peor['puesto'] ?> de <?= (int) $g['total'] ?>
                                &middot; promedio <strong><?= e(number_format((float) $peor['promedio_general'], 2)) ?></strong>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
