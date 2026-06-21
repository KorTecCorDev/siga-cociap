<?php
/**
 * @var array  $niveles     [{ id, nombre }]
 * @var int    $nivelActivo
 * @var array  $areas       [{ id, nombre, tipo, activa, orden }]
 * @var int    $areaId
 * @var ?array $area        { id, nombre, nombre_boleta, alias_boleta, nombre_siagie,
 *                            tipo, orden, activa, nivel_id, nivel_nombre,
 *                            subareas:     [{id, nombre, orden, competencias: [...]}],
 *                            competencias: [{id, codigo_minedu, nombre_completo, nombre_corto, orden}] }
 */

$tipoBadge = fn(string $tipo): string => match($tipo) {
    'con_subareas' => 'Con subáreas',
    'area_curso'   => 'Área-curso',
    'transversal'  => 'Transversal',
    default        => $tipo,
};

$tipoAbrev = fn(string $tipo): string => match($tipo) {
    'con_subareas' => 'C/sub',
    'transversal'  => 'Transv.',
    default        => 'Curso',
};

$primerAreaId = !empty($areas) ? (int)$areas[0]['id'] : 0;
$ultimaAreaId = !empty($areas) ? (int)end($areas)['id'] : 0;

// Reúne todas las competencias del área para renderizar sus modales al final
$todasLasCompetencias = [];
if ($area) {
    if ($area['tipo'] === 'con_subareas') {
        foreach ($area['subareas'] as $sa) {
            foreach ($sa['competencias'] as $c) {
                $todasLasCompetencias[] = $c;
            }
        }
    } else {
        $todasLasCompetencias = $area['competencias'];
    }
}
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">&#8592; Dashboard</a>
    <div>
        <h1 class="page-title">Currículo Académico</h1>
        <p class="page-subtitle">Áreas, subáreas y competencias del MINEDU</p>
    </div>
</div>

<div class="curr-layout">

    <!-- ── Panel lateral ──────────────────────────────────────── -->
    <aside class="curr-sidebar">

        <div class="curr-sidebar__tabs">
            <?php foreach ($niveles as $n): ?>
            <a href="<?= e(url('admin/curriculum?nivel=' . $n['id'])) ?>"
               class="curr-sidebar__tab<?= $nivelActivo == $n['id'] ? ' curr-sidebar__tab--activo' : '' ?>">
                <?= e($n['nombre']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <nav class="curr-sidebar__nav">
            <?php if (empty($areas)): ?>
            <p class="curr-sidebar__vacio">Sin áreas registradas.</p>
            <?php else: ?>
            <?php foreach ($areas as $a): ?>
            <div class="curr-sidebar__item-row<?= $areaId == $a['id'] ? ' curr-sidebar__item-row--activo' : '' ?><?= !$a['activa'] ? ' curr-sidebar__item-row--inactiva' : '' ?>">

                <a href="<?= e(url('admin/curriculum?nivel=' . $nivelActivo . '&area=' . $a['id'])) ?>"
                   class="curr-sidebar__item">
                    <span class="curr-sidebar__item-nombre"><?= e($a['nombre']) ?></span>
                    <span class="curr-tipo-badge curr-tipo-badge--<?= e($a['tipo']) ?>">
                        <?= e($tipoAbrev($a['tipo'])) ?>
                    </span>
                </a>

                <div class="curr-sidebar__item-mover">
                    <form method="POST"
                          action="<?= e(url('admin/curriculum/areas/' . $a['id'] . '/mover')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="nivel_id"  value="<?= $nivelActivo ?>">
                        <input type="hidden" name="area_id"   value="<?= $areaId ?>">
                        <input type="hidden" name="direccion" value="up">
                        <button type="submit" class="curr-mover-btn" title="Mover arriba"
                                <?= $a['id'] == $primerAreaId ? 'disabled' : '' ?>>&#8593;</button>
                    </form>
                    <form method="POST"
                          action="<?= e(url('admin/curriculum/areas/' . $a['id'] . '/mover')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="nivel_id"  value="<?= $nivelActivo ?>">
                        <input type="hidden" name="area_id"   value="<?= $areaId ?>">
                        <input type="hidden" name="direccion" value="down">
                        <button type="submit" class="curr-mover-btn" title="Mover abajo"
                                <?= $a['id'] == $ultimaAreaId ? 'disabled' : '' ?>>&#8595;</button>
                    </form>
                </div>

            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </nav>

    </aside>

    <!-- ── Panel de detalle ───────────────────────────────────── -->
    <section class="curr-detail">

        <?php if (!$area): ?>
        <p class="curr-detail__vacio">Selecciona un área del panel lateral.</p>

        <?php else: ?>

        <!-- Header del área -->
        <div class="curr-detail__header">
            <div class="curr-detail__header-info">
                <div class="curr-detail__header-top">
                    <h2 class="curr-detail__nombre"><?= e($area['nombre']) ?></h2>
                    <span class="curr-tipo-badge curr-tipo-badge--<?= e($area['tipo']) ?> curr-tipo-badge--md">
                        <?= e($tipoBadge($area['tipo'])) ?>
                    </span>
                    <?php if (!$area['activa']): ?>
                    <span class="curr-badge-inactiva">Inactiva</span>
                    <?php endif; ?>
                </div>
                <?php if ($area['nombre_boleta'] || $area['nombre_siagie']): ?>
                <div class="curr-detail__meta">
                    <?php if ($area['nombre_boleta']): ?>
                    <span class="curr-meta-item">
                        <strong>Boleta:</strong>
                        <?= e($area['nombre_boleta']) ?>
                        <?php if ($area['alias_boleta']): ?>
                        <em><?= e($area['alias_boleta']) ?></em>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($area['nombre_siagie']): ?>
                    <span class="curr-meta-item">
                        <strong>SIAGIE:</strong> <?= e($area['nombre_siagie']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="curr-detail__header-actions">
                <button type="button" class="btn btn--secondary btn--sm"
                        onclick="Modal.abrir('modal-area-<?= $area['id'] ?>')">
                    Editar área
                </button>
                <form method="POST"
                      action="<?= e(url('admin/curriculum/areas/' . $area['id'] . '/toggle')) ?>"
                      class="curr-toggle-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="nivel_id" value="<?= (int)$area['nivel_id'] ?>">
                    <button type="submit"
                            class="btn btn--sm <?= $area['activa'] ? 'btn--danger' : 'btn--primary' ?>">
                        <?= $area['activa'] ? 'Desactivar' : 'Activar' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Contenido: área con subáreas -->
        <?php if ($area['tipo'] === 'con_subareas'): ?>

            <?php if (empty($area['subareas'])): ?>
            <p class="curr-detail__vacio">Sin subáreas registradas para esta área.</p>
            <?php endif; ?>

            <?php foreach ($area['subareas'] as $sa): ?>
            <div class="curr-subarea">
                <div class="curr-subarea__header">
                    <div class="curr-subarea__titulo">
                        <span class="curr-subarea__nombre"><?= e($sa['nombre']) ?></span>
                        <span class="curr-subarea__orden">orden <?= (int)$sa['orden'] ?></span>
                    </div>
                    <button type="button" class="btn btn--secondary btn--sm"
                            onclick="Modal.abrir('modal-subarea-<?= $sa['id'] ?>')">
                        Editar
                    </button>
                </div>

                <?php foreach ($sa['competencias'] as $c): ?>
                <div class="curr-competencia">
                    <?php if ($c['codigo_minedu']): ?>
                    <span class="curr-competencia__codigo"><?= e($c['codigo_minedu']) ?></span>
                    <?php endif; ?>
                    <div class="curr-competencia__info">
                        <span class="curr-competencia__nombre-completo"><?= e($c['nombre_completo']) ?></span>
                        <?php if ($c['nombre_corto']): ?>
                        <span class="curr-competencia__nombre-corto"><?= e($c['nombre_corto']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn--secondary btn--sm"
                            onclick="Modal.abrir('modal-comp-<?= $c['id'] ?>')">
                        Editar
                    </button>
                </div>
                <?php endforeach; ?>

                <?php if (empty($sa['competencias'])): ?>
                <p class="curr-subarea__sin-comp">Sin competencias registradas.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
        <!-- Contenido: área-curso o transversal — competencias directas -->

            <?php if (empty($area['competencias'])): ?>
            <p class="curr-detail__vacio">Sin competencias registradas para esta área.</p>
            <?php endif; ?>

            <div class="curr-competencias-lista">
                <?php foreach ($area['competencias'] as $c): ?>
                <div class="curr-competencia">
                    <?php if ($c['codigo_minedu']): ?>
                    <span class="curr-competencia__codigo"><?= e($c['codigo_minedu']) ?></span>
                    <?php endif; ?>
                    <div class="curr-competencia__info">
                        <span class="curr-competencia__nombre-completo"><?= e($c['nombre_completo']) ?></span>
                        <?php if ($c['nombre_corto']): ?>
                        <span class="curr-competencia__nombre-corto"><?= e($c['nombre_corto']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn--secondary btn--sm"
                            onclick="Modal.abrir('modal-comp-<?= $c['id'] ?>')">
                        Editar
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
        <?php endif; // end if $area ?>

    </section>
</div>

<?php if (!$area) return; ?>

<!-- ══════════════════════════════════════════════════════════════
     Modales — solo se renderizan para el área actualmente visible
     ══════════════════════════════════════════════════════════════ -->

<!-- Modal: Editar área -->
<div id="modal-area-<?= $area['id'] ?>" class="modal-overlay" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Editar área</h3>
            <button type="button" class="modal-cerrar"
                    data-modal-cerrar="modal-area-<?= $area['id'] ?>">&#215;</button>
        </div>
        <form method="POST"
              action="<?= e(url('admin/curriculum/areas/' . $area['id'] . '/editar')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="nivel_id" value="<?= (int)$area['nivel_id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="area-nombre">Nombre</label>
                    <input type="text" id="area-nombre" name="nombre" class="form-input"
                           value="<?= e($area['nombre']) ?>" required maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label" for="area-nombre-boleta">Nombre en boleta</label>
                    <input type="text" id="area-nombre-boleta" name="nombre_boleta"
                           class="form-input" maxlength="120"
                           value="<?= e($area['nombre_boleta'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="area-alias-boleta">Alias en boleta</label>
                    <input type="text" id="area-alias-boleta" name="alias_boleta"
                           class="form-input" maxlength="80"
                           placeholder="Ej: (Ética y Valores)"
                           value="<?= e($area['alias_boleta'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="area-nombre-siagie">Nombre SIAGIE</label>
                    <input type="text" id="area-nombre-siagie" name="nombre_siagie"
                           class="form-input" maxlength="120"
                           value="<?= e($area['nombre_siagie'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="area-orden">Orden</label>
                    <input type="number" id="area-orden" name="orden"
                           class="form-input curr-input-orden"
                           value="<?= (int)$area['orden'] ?>" min="0" max="99">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary"
                        data-modal-cerrar="modal-area-<?= $area['id'] ?>">
                    <span class="btn-icon btn-icon--back" aria-hidden="true"></span>
                    Cancelar
                </button>
                <button type="submit" class="btn btn--primary">
                    <span class="btn-icon btn-icon--save" aria-hidden="true"></span>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modales: subáreas -->
<?php foreach ($area['subareas'] as $sa): ?>
<div id="modal-subarea-<?= $sa['id'] ?>" class="modal-overlay" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Editar subárea</h3>
            <button type="button" class="modal-cerrar"
                    data-modal-cerrar="modal-subarea-<?= $sa['id'] ?>">&#215;</button>
        </div>
        <form method="POST"
              action="<?= e(url('admin/curriculum/subareas/' . $sa['id'] . '/editar')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="area_id"  value="<?= (int)$area['id'] ?>">
            <input type="hidden" name="nivel_id" value="<?= (int)$area['nivel_id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="sa-nombre-<?= $sa['id'] ?>">Nombre</label>
                    <input type="text" id="sa-nombre-<?= $sa['id'] ?>" name="nombre"
                           class="form-input" value="<?= e($sa['nombre']) ?>"
                           required maxlength="80">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sa-orden-<?= $sa['id'] ?>">Orden</label>
                    <input type="number" id="sa-orden-<?= $sa['id'] ?>" name="orden"
                           class="form-input curr-input-orden"
                           value="<?= (int)$sa['orden'] ?>" min="0" max="99">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary"
                        data-modal-cerrar="modal-subarea-<?= $sa['id'] ?>">Cancelar</button>
                <button type="submit" class="btn btn--primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Modales: competencias -->
<?php foreach ($todasLasCompetencias as $c): ?>
<div id="modal-comp-<?= $c['id'] ?>" class="modal-overlay" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Editar competencia</h3>
            <button type="button" class="modal-cerrar"
                    data-modal-cerrar="modal-comp-<?= $c['id'] ?>">&#215;</button>
        </div>
        <form method="POST"
              action="<?= e(url('admin/curriculum/competencias/' . $c['id'] . '/editar')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="area_id"  value="<?= (int)$area['id'] ?>">
            <input type="hidden" name="nivel_id" value="<?= (int)$area['nivel_id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="c-codigo-<?= $c['id'] ?>">Código MINEDU</label>
                    <input type="text" id="c-codigo-<?= $c['id'] ?>" name="codigo_minedu"
                           class="form-input curr-input-codigo"
                           value="<?= e($c['codigo_minedu'] ?? '') ?>" maxlength="5"
                           placeholder="Ej: C14">
                </div>
                <div class="form-group">
                    <label class="form-label" for="c-nombre-<?= $c['id'] ?>">Nombre completo</label>
                    <textarea id="c-nombre-<?= $c['id'] ?>" name="nombre_completo"
                              class="form-input" rows="3" required><?= e($c['nombre_completo']) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c-corto-<?= $c['id'] ?>">Nombre corto</label>
                    <input type="text" id="c-corto-<?= $c['id'] ?>" name="nombre_corto"
                           class="form-input" value="<?= e($c['nombre_corto'] ?? '') ?>"
                           maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label" for="c-orden-<?= $c['id'] ?>">Orden</label>
                    <input type="number" id="c-orden-<?= $c['id'] ?>" name="orden"
                           class="form-input curr-input-orden"
                           value="<?= (int)$c['orden'] ?>" min="0" max="99">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary"
                        data-modal-cerrar="modal-comp-<?= $c['id'] ?>">
                    <span class="btn-icon btn-icon--back" aria-hidden="true"></span>
                    Cancelar
                </button>
                <button type="submit" class="btn btn--primary">
                    <span class="btn-icon btn-icon--save" aria-hidden="true"></span>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
