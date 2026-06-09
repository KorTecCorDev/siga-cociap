<?php
/**
 * @var array $matriculas
 * @var array $filtros
 * @var array $anios
 * @var array $grados
 * @var array $secciones
 * @var int   $total
 * @var int   $pagina
 * @var int   $total_pags
 */

// Etiquetas legibles de estado.
$labelEstado = fn(string $e): string => match($e) {
    'pendiente'   => 'Pendiente',
    'aprobada'    => 'Aprobado',
    'desactivado' => 'Desactivado',
    default       => ucfirst($e),
};
$labelTipo = fn(string $t): string => match($t) {
    'continuador' => 'Continuador',
    'nuevo'       => 'Nuevo',
    'trasladado'  => 'Trasladado',
    default       => ucfirst($t),
};

// Conserva los filtros actuales al paginar.
$qs = fn(int $p): string => http_build_query(array_filter([
    'anio_id'    => $filtros['anio_id']    ?? null,
    'grado_id'   => $filtros['grado_id']   ?? null,
    'seccion_id' => $filtros['seccion_id'] ?? null,
    'estado'     => $filtros['estado']     ?? null,
    'tipo'       => $filtros['tipo']       ?? null,
    'search'     => $filtros['search']     ?? null,
    'pagina'     => $p,
]));
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Matrículas</h1>
        <p class="page-subtitle"><?= (int) $total ?> matrícula<?= $total !== 1 ? 's' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <div class="btn-group">
        <a href="<?= url('traslados') ?>" class="btn btn--secondary">Traslados</a>
        <a href="<?= url('matriculas/crear') ?>" class="btn btn--primary">+ Nueva matrícula</a>
    </div>
</div>

<!-- ── Filtros ─────────────────────────────────────────── -->
<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= url('matriculas') ?>">
            <div class="mat-filtros">
                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="anio_id">Año académico</label>
                    <select id="anio_id" name="anio_id" class="form-input">
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= (int) ($filtros['anio_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
                                <?= e((string) $a['anio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="grado_id">Grado</label>
                    <select id="grado_id" name="grado_id" class="form-input">
                        <option value="">Todos</option>
                        <?php foreach ($grados as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= (int) ($filtros['grado_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>>
                                <?= e($g['nivel_nombre'] . ' — ' . $g['nombre_display']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="seccion_id">Sección</label>
                    <select id="seccion_id" name="seccion_id" class="form-input">
                        <option value="">Todas</option>
                        <?php foreach ($secciones as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int) ($filtros['seccion_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['grado_nombre'] . ' ' . $s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-input">
                        <option value="">Todos</option>
                        <?php foreach (['pendiente' => 'Pendiente', 'aprobada' => 'Aprobado', 'desactivado' => 'Desactivado'] as $val => $lab): ?>
                            <option value="<?= $val ?>" <?= ($filtros['estado'] ?? '') === $val ? 'selected' : '' ?>><?= $lab ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" class="form-input">
                        <option value="">Todos</option>
                        <?php foreach (['continuador' => 'Continuador', 'nuevo' => 'Nuevo', 'trasladado' => 'Trasladado'] as $val => $lab): ?>
                            <option value="<?= $val ?>" <?= ($filtros['tipo'] ?? '') === $val ? 'selected' : '' ?>><?= $lab ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="search">DNI o nombre</label>
                    <input type="text" id="search" name="search" class="form-input"
                           value="<?= e((string) ($filtros['search'] ?? '')) ?>" placeholder="Buscar...">
                </div>

                <div class="mat-filtros__acciones">
                    <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                    <a href="<?= url('matriculas') ?>" class="btn btn--secondary btn--sm">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($matriculas)): ?>
    <div class="card"><div class="card__body"><div class="empty-state">
        <p>No hay matrículas que coincidan con los filtros.</p>
    </div></div></div>
<?php else: ?>

<div class="card">
    <div class="tabla-notas-wrapper">
        <table class="tabla-notas">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Estudiante</th>
                    <th>DNI</th>
                    <th>Grado / Sección</th>
                    <th>Tipo</th>
                    <th class="text-center">Estado</th>
                    <th>Apoderado responsable</th>
                    <th>Registro</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = ($pagina - 1) * 25; foreach ($matriculas as $m): $i++; ?>
                <tr>
                    <td class="text-sm"><?= $i ?></td>
                    <td><?= e($m['nombre_completo']) ?></td>
                    <td class="text-sm"><?= e($m['dni']) ?></td>
                    <td class="text-sm">
                        <?= e(($m['grado_nombre'] ?? '—') . ' ' . ($m['seccion_nombre'] ?? '')) ?>
                    </td>
                    <td>
                        <span class="matricula-badge matricula-badge--<?= e($m['tipo']) ?>"><?= $labelTipo($m['tipo']) ?></span>
                    </td>
                    <td class="text-center">
                        <span class="matricula-badge matricula-badge--<?= e($m['estado']) ?>"><?= $labelEstado($m['estado']) ?></span>
                        <?php if (!empty($m['motivo_estado'])): ?>
                        <span class="matricula-motivo"><?= e($m['motivo_estado']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm"><?= e($m['apoderado_responsable'] ?? '—') ?></td>
                    <td class="text-sm text-muted"><?= $m['fecha_registro'] ? fecha_es($m['fecha_registro']) : '—' ?></td>
                    <td class="text-right">
                        <a href="<?= url('matriculas/' . $m['id']) ?>" class="btn btn--secondary btn--sm">Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pags > 1): ?>
<div class="mat-paginacion">
    <?php if ($pagina > 1): ?>
        <a href="<?= url('matriculas?' . $qs($pagina - 1)) ?>" class="btn btn--secondary btn--sm">← Anterior</a>
    <?php endif; ?>
    <span class="mat-paginacion__info">Página <?= $pagina ?> de <?= $total_pags ?></span>
    <?php if ($pagina < $total_pags): ?>
        <a href="<?= url('matriculas?' . $qs($pagina + 1)) ?>" class="btn btn--secondary btn--sm">Siguiente →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>
