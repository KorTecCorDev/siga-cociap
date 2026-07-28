<?php
/**
 * Vista: Actas SIAGIE — Vínculos y cobertura (SOLO LECTURA).
 *
 * Responde "¿qué notas de SIGA no están llegando al acta oficial?". El vínculo
 * se EDITA en Currículo (campo "Codigo de hoja SIAGIE" del área); aquí solo se
 * diagnostica, para que un área sin código deje de perderse en silencio.
 *
 * La tabla de vínculos parte de las ÁREAS, no de las notas: un vínculo existe
 * aunque el bimestre no tenga nada registrado todavía.
 *
 * @var array  $periodos    [{ id, nombre_display, estado, numero }]
 * @var int    $periodoId
 * @var array  $vinculos    [{ area_id, area_nombre, codigo_siagie, area_tipo, activa,
 *                             nivel_id, nivel_nombre, nivel_codigo, alumnos,
 *                             competencias, notas,
 *                             estado: {clave, etiqueta, tono, detalle} }]
 * @var array  $excepciones [{ codigo_hoja, grados, columnas, motivo, competencia,
 *                             error, nivel_nombre }]
 * @var array  $duplicados  [{ nivel_nombre, codigo_siagie, areas }]
 */

$sinDestino    = array_filter($vinculos, static fn($v) => $v['estado']['clave'] === 'sin_destino');
$notasPerdidas = array_sum(array_column($sinDestino, 'notas'));
?>

<div class="page-header">
    <div>
        <a href="<?= url('admin/actas-siagie') ?>" class="btn btn--secondary btn--sm">&larr; Actas SIAGIE</a>
        <h1 class="page-title">Vinculos y cobertura</h1>
        <p class="page-subtitle">Que areas de SIGA llegan al acta oficial del SIAGIE, y cuales se estan quedando fuera</p>
    </div>
</div>

<div class="actas-siagie">

    <div class="card mb-lg">
        <div class="card__body">
            <form method="GET" action="<?= url('admin/actas-siagie/vinculos') ?>" class="actas-filtro">
                <label class="form-label" for="periodo">Bimestre</label>
                <select name="periodo" id="periodo" class="form-input" onchange="this.form.submit()">
                    <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $periodoId ? 'selected' : '' ?>>
                        <?= e($p['nombre_display']) ?> (<?= e($p['estado']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn--secondary btn--sm">Ver</button></noscript>
            </form>
        </div>
    </div>

    <?php if ($duplicados): ?>
    <div class="alert alert--error mb-lg">
        <strong>Codigos de hoja duplicados.</strong> Dos areas del mismo nivel comparten codigo:
        la hoja se asignaria a una de ellas de forma arbitraria y el acta se llenaria con el area
        equivocada. Corrigelo en Curriculo.
        <ul class="actas-lista">
            <?php foreach ($duplicados as $d): ?>
            <li><?= e($d['nivel_nombre']) ?> &mdash; <code><?= e($d['codigo_siagie']) ?></code>: <?= e($d['areas']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- ── Lo que se esta perdiendo ────────────────────────────────── -->
    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title">Areas con notas y SIN destino en el SIAGIE</h2>
            <?php if ($sinDestino): ?>
            <span class="badge badge--error"><?= count($sinDestino) ?></span>
            <?php else: ?>
            <span class="badge badge--activo">ninguna</span>
            <?php endif; ?>
        </div>
        <div class="card__body">
            <?php if (!$sinDestino): ?>
                <p class="actas-hint">
                    Todas las areas con notas bloqueadas en este bimestre tienen destino.
                </p>
            <?php else: ?>
                <p class="actas-hint">
                    Estas areas tienen notas <strong>bloqueadas</strong> pero no se vuelcan a ninguna
                    hoja: el acta sale sin ellas y <strong>sin ningun aviso</strong>. Si el SIAGIE ya
                    reconoce el area, asignale su codigo de hoja en Curriculo.
                </p>
                <div class="actas-metricas">
                    <div class="actas-metrica actas-metrica--warn">
                        <div class="actas-metrica__num"><?= (int) $notasPerdidas ?></div>
                        <div class="actas-metrica__lbl">notas fuera del acta</div>
                    </div>
                    <div class="actas-metrica actas-metrica--warn">
                        <div class="actas-metrica__num"><?= count($sinDestino) ?></div>
                        <div class="actas-metrica__lbl">areas sin destino</div>
                    </div>
                </div>
                <table class="tabla-resumen">
                    <thead>
                        <tr>
                            <th>Nivel</th>
                            <th class="col-nombre">Area</th>
                            <th class="col-num">Alumnos</th>
                            <th class="col-num">Notas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sinDestino as $v): ?>
                        <tr>
                            <td><?= e($v['nivel_nombre']) ?></td>
                            <td class="col-nombre"><?= e($v['area_nombre']) ?></td>
                            <td class="col-num"><?= (int) $v['alumnos'] ?></td>
                            <td class="col-num"><?= (int) $v['notas'] ?></td>
                            <td>
                                <a class="btn btn--secondary btn--sm"
                                   href="<?= url('admin/curriculum?nivel=' . (int) $v['nivel_id'] . '&area=' . (int) $v['area_id']) ?>">
                                    Asignar codigo
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Vinculos configurados ───────────────────────────────────── -->
    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title">Vinculos configurados (hoja &rarr; area)</h2>
        </div>
        <div class="card__body">
            <p class="actas-hint">
                Todas las areas del curriculo con su hoja del SIAGIE. Las notas son las de este
                bimestre y pueden ser 0: el vinculo existe igual. El codigo se edita en
                <strong>Curriculo &rarr; Editar area</strong>.
            </p>
            <?php if (!$vinculos): ?>
                <p class="actas-hint">No hay areas registradas.</p>
            <?php else: ?>
            <table class="tabla-resumen">
                <thead>
                    <tr>
                        <th>Nivel</th>
                        <th class="col-nombre">Area</th>
                        <th>Hoja</th>
                        <th class="col-num">Alumnos</th>
                        <th class="col-num">Notas</th>
                        <th class="col-conclusion">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vinculos as $v):
                        $codigo = trim((string) $v['codigo_siagie']);
                        $est    = $v['estado']; ?>
                    <tr>
                        <td><?= e($v['nivel_nombre']) ?></td>
                        <td class="col-nombre">
                            <?= e($v['area_nombre']) ?>
                            <?php if (!$v['activa']): ?>
                            <span class="badge badge--sin-notas">inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($est['hoja'] !== null): ?>
                                <code><?= e($est['hoja']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-num"><?= (int) $v['alumnos'] ?></td>
                        <td class="col-num"><?= (int) $v['notas'] ?></td>
                        <td class="col-conclusion">
                            <span class="badge badge--<?= e($est['tono']) ?>"><?= e($est['etiqueta']) ?></span>
                            <?php if ($est['detalle'] !== ''): ?>
                            <br><span class="text-muted"><?= e($est['detalle']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Excepciones de hoja ─────────────────────────────────────── -->
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Excepciones de hoja</h2>
        </div>
        <div class="card__body">
            <p class="actas-hint">
                Casos en que la hoja del SIAGIE espera un area que en SIGA <strong>no</strong> es la
                que evalua esa competencia. Estan declaradas en el codigo del llenador (no se editan
                aqui) y se muestran para que se sepa por que un acta se llena asi.
            </p>
            <?php if (!$excepciones): ?>
                <p class="actas-hint">Sin excepciones declaradas para estos niveles.</p>
            <?php else: ?>
            <table class="tabla-resumen">
                <thead>
                    <tr>
                        <th>Nivel</th>
                        <th>Hoja</th>
                        <th>Grados</th>
                        <th class="col-nombre">Se llena con</th>
                        <th>Columnas</th>
                        <th class="col-conclusion">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($excepciones as $x): ?>
                    <tr>
                        <td><?= e($x['nivel_nombre']) ?></td>
                        <td><code><?= e($x['codigo_hoja']) ?></code></td>
                        <td><?= $x['grados'] === null ? 'todos' : e(implode('°, ', $x['grados'])) . '°' ?></td>
                        <td class="col-nombre">
                            <?php if ($x['competencia']): ?>
                                <?= e($x['competencia']['nombre_completo']) ?>
                                <br><span class="text-muted">
                                    <?= e($x['competencia']['area_nombre']) ?>
                                    &middot; <?= e($x['competencia']['codigo_minedu'] ?? '') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge--error">no se pudo resolver</span>
                                <br><span class="text-muted"><?= e((string) $x['error']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $x['columnas'] === null
                                ? '<span class="badge badge--info">todas</span>'
                                : e(implode(', ', $x['columnas'])) ?>
                        </td>
                        <td class="col-conclusion"><?= e($x['motivo']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
