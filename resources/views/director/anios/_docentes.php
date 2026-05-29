<?php
/**
 * Ranking de docentes que cerraron sus competencias más rápido.
 * Reutilizado por el modal de cierre (show.php) y por la página de
 * indicadores (stats.php). Espera la variable $docentes.
 *
 * @var array $docentes
 */
$docentes = $docentes ?? [];
?>
<section class="stats-bloque">
    <h3 class="stats-bloque__titulo">Docentes más rápidos en cerrar sus competencias</h3>
    <p class="text-muted text-sm">
        Docentes que bloquearon el 100&nbsp;% de sus competencias, ordenados por mayor
        anticipación frente a la fecha límite del bimestre.
    </p>

    <?php if (empty($docentes)): ?>
        <p class="text-muted text-sm">Ningún docente ha bloqueado todas sus competencias todavía.</p>
    <?php else: ?>
        <ol class="stats-docentes">
            <?php foreach ($docentes as $i => $d): ?>
            <li class="stats-docente">
                <span class="stats-docente__pos"><?= $i + 1 ?></span>
                <div class="stats-docente__info">
                    <span class="stats-docente__nombre"><?= e($d['nombre_completo']) ?></span>
                    <span class="stats-docente__meta">
                        <?= (int) $d['total_comp'] ?> competencia<?= (int) $d['total_comp'] !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <span class="stats-docente__margen stats-docente__margen--<?= $d['a_tiempo'] ? 'ok' : 'tarde' ?>">
                    <?php if ($d['a_tiempo']): ?>
                        <?= e($d['margen']) ?> antes
                    <?php else: ?>
                        <?= e($d['margen']) ?> tarde
                    <?php endif; ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
