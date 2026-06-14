<?php
/**
 * Nómina de matriculados — buscador en vivo + impresión por sección.
 * Sin DNI: dato sensible de consulta restringida.
 * @var array $alumnos    filas planas (matriculados aprobados)
 * @var array $secciones  [{seccion_id, nivel_nombre, grado_nombre, seccion_nombre, n}]
 * @var int   $total
 */
?>

<div class="page-header">
    <a href="<?= url('docente/inicio') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Nómina de matriculados</h1>
        <p class="page-subtitle"><?= $total ?> estudiantes aprobados en tus niveles</p>
    </div>
</div>

<?php if (empty($alumnos)): ?>
    <div class="empty-state"><p>No hay matriculados aprobados en los niveles donde tienes cargas.</p></div>
<?php else: ?>

<!-- Imprimir nómina de una sección -->
<div class="card mb-md">
    <div class="card__body">
        <label class="form-label" for="nomina-seccion">Imprimir nómina de una sección</label>
        <div class="nomina-imprimir">
            <select id="nomina-seccion" class="form-input">
                <option value="">Selecciona una sección…</option>
                <?php foreach ($secciones as $s): ?>
                    <option value="<?= (int) $s['seccion_id'] ?>">
                        <?= e($s['nivel_nombre']) ?> · <?= e($s['grado_nombre']) ?> — Sección <?= e($s['seccion_nombre']) ?> (<?= (int) $s['n'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <a id="nomina-imprimir-btn" class="btn btn--primary is-disabled"
               aria-disabled="true" href="#" target="_blank" rel="noopener"
               data-base="<?= url('docente/nomina') ?>">
                <span class="btn-icon btn-icon--print" aria-hidden="true"></span>
                Imprimir
            </a>
        </div>
    </div>
</div>

<!-- Buscador -->
<div class="card mb-md">
    <div class="card__body">
        <input type="search" id="nomina-buscador" class="form-input"
               placeholder="Buscar estudiante por apellidos o nombres…" autocomplete="off">
        <p class="text-sm text-muted" id="nomina-hint">Escribe para buscar entre <?= $total ?> estudiantes.</p>
        <p class="text-sm text-muted" id="nomina-sin-resultados" hidden>Sin coincidencias.</p>
    </div>
</div>

<!-- Resultados (ocultos hasta buscar) -->
<div class="tabla-notas-wrapper" id="nomina-resultados" hidden>
    <table class="tabla-resumen nomina-tabla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th>Apellidos y nombres</th>
                <th>Grado y sección</th>
                <th>Apoderado responsable</th>
                <th>Celular</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alumnos as $a): ?>
                <?php $nombre = $a['apellido_paterno'] . ' ' . $a['apellido_materno'] . ', ' . $a['nombres']; ?>
                <tr class="nomina-fila" data-buscar="<?= e(mb_strtolower($nombre)) ?>" hidden>
                    <td class="col-num nomina-num"></td>
                    <td><?= e($nombre) ?></td>
                    <td><?= e($a['grado_nombre'] . ' ' . $a['seccion_nombre']) ?></td>
                    <td><?= $a['apoderado_nombre'] !== '' ? e($a['apoderado_nombre']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $a['apoderado_telefono'] ? e($a['apoderado_telefono']) : '<span class="text-muted">—</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
