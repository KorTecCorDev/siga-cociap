<?php
/**
 * Conducta — grilla de criterios Si/No de los auxiliares (RA) en SOLO LECTURA
 * para el tutor. Espejo de admin/conducta/seccion.php en su estado bloqueado:
 * mismos estilos (.conducta-grilla, .cc-toggle, .cc-btn deshabilitados), sin
 * formularios, sin JS y sin endpoints de escritura. La nota RA se calcula en
 * el servidor (Si / total x 20, redondeo .5 a favor), no via JS como en admin.
 *
 * @var array $seccion       { id, nombre, grado_nombre, nivel_nombre, nivel_id }
 * @var array $periodo       { id, numero, nombre_display, estado }
 * @var array $cierre        cierre vigente (ra_bloqueado_en, tutor_cerrado_en)
 * @var array $criterios     [{ id, texto, orden }]
 * @var array $estudiantes   [{ matricula_id, nombre_completo, respuestas[criterio_id] }]
 * @var bool  $hayRespuestas false = bimestre legado (B1, literal directo)
 */

$total = count($criterios);
$pid   = (int) $periodo['id'];
?>

<div class="page-header">
    <a href="<?= url('docente/conducta/' . $pid) ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title page-title--wf page-title--conducta">
            Criterios de conducta — Sección <?= e($seccion['nombre']) ?>
        </h1>
        <p class="page-subtitle">
            <?= e($seccion['grado_nombre']) ?> <?= e($seccion['nivel_nombre']) ?>
            · <strong><?= e($periodo['nombre_display']) ?></strong>
            · Solo lectura
        </p>
    </div>
</div>

<div class="alert alert--info">
    <span class="btn-icon btn-icon--locked" aria-hidden="true"></span>
    <span>
        Registro de los auxiliares académicos, <strong>bloqueado y aprobado por
        Registro Académico</strong> el <?= e(fechaLima($cierre['ra_bloqueado_en'])) ?>.
        Esta vista es de consulta: cualquier corrección se solicita a Registro Académico.
    </span>
</div>

<?php if (!$hayRespuestas): ?>

    <div class="empty-state">
        <p>Este bimestre se registró con el modelo anterior (calificación literal
        directa), por lo que no hay una matriz de criterios que mostrar.</p>
    </div>

<?php elseif (empty($estudiantes)): ?>

    <div class="empty-state"><p>No hay estudiantes matriculados en esta sección.</p></div>

<?php else: ?>

<details class="conducta-criterios-leyenda">
    <summary>Ver los <?= $total ?> criterios (✓ = cumple · ✗ = no cumple)</summary>
    <ol class="conducta-criterios-lista">
        <?php foreach ($criterios as $c): ?>
            <li><?= e($c['texto']) ?></li>
        <?php endforeach; ?>
    </ol>
</details>

<div class="tabla-notas-wrapper conducta-scroll">
    <table class="tabla-notas conducta-grilla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-nombre">Apellidos y Nombres</th>
                <?php foreach ($criterios as $i => $c): ?>
                    <th class="conducta-th-crit" title="<?= e($c['texto']) ?>">C<?= $i + 1 ?></th>
                <?php endforeach; ?>
                <th class="conducta-th-nota" title="Nota de Registro Académico (Sí ÷ <?= $total ?> × 20)">Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $idx => $est):
                $resp        = $est['respuestas'];
                $respondidos = count($resp);
                $si          = count(array_filter($resp, fn($v) => (int) $v === 1));
                $notaRa      = ($respondidos >= $total && $total > 0)
                    ? (int) round(($si / $total) * 20, 0, PHP_ROUND_HALF_UP)
                    : null;
                $litRa       = $notaRa !== null ? nota_a_literal($notaRa) : null;
            ?>
                <tr class="conducta-fila conducta-fila--guardada">
                    <td class="col-num"><?= $idx + 1 ?></td>
                    <td class="col-nombre"><?= e($est['nombre_completo']) ?></td>

                    <?php foreach ($criterios as $c):
                        $val = $resp[(int) $c['id']] ?? null; // null | 0 | 1
                    ?>
                        <td class="conducta-td-crit">
                            <div class="cc-toggle" role="group"
                                 aria-label="Criterio para <?= e($est['nombre_completo']) ?> (solo lectura)">
                                <button type="button" class="cc-btn cc-btn--si<?= $val === 1 ? ' cc-btn--activo' : '' ?>"
                                        title="Cumple" aria-label="Cumple" disabled>✓</button>
                                <button type="button" class="cc-btn cc-btn--no<?= $val === 0 ? ' cc-btn--activo' : '' ?>"
                                        title="No cumple" aria-label="No cumple" disabled>✗</button>
                            </div>
                        </td>
                    <?php endforeach; ?>

                    <td class="conducta-td-nota">
                        <?php if ($notaRa !== null): ?>
                            <span class="nota-numeral nota-numeral--<?= strtolower($litRa) ?>">
                                <?= fmt_nota($notaRa) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted" title="Registro incompleto">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
