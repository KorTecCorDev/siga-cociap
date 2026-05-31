<?php
/**
 * Historial de reaperturas de un bimestre. Se muestra en el modal de
 * indicadores (show.php). Cada item: fecha/hora, autor, motivo y cuántos
 * bloqueos sin notas se liberaron en esa reapertura.
 *
 * @var array $reaperturas  filas de reaperturas_periodo + reabierto_por_nombre
 */
$reaperturas = $reaperturas ?? [];
if (!empty($reaperturas)):
?>
<div class="reaperturas-historial">
    <h3 class="reaperturas-historial__titulo">
        Historial de reaperturas
        <span class="reaperturas-historial__contador"><?= count($reaperturas) ?></span>
    </h3>
    <ul class="reaperturas-list">
        <?php foreach ($reaperturas as $r): ?>
        <li class="reapertura-item">
            <div class="reapertura-item__head">
                <span class="reapertura-item__fecha"><?= e(fechaLima($r['reabierto_en'])) ?></span>
                <span class="reapertura-item__autor"><?= e($r['reabierto_por_nombre']) ?></span>
            </div>
            <p class="reapertura-item__motivo"><?= e($r['motivo']) ?></p>
            <?php if ((int) $r['bloqueos_liberados'] > 0): ?>
            <p class="reapertura-item__meta">
                <?= (int) $r['bloqueos_liberados'] ?> competencia(s) sin notas liberadas
            </p>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
