<?php
/**
 * Nómina de matriculados — buscador en vivo + impresión por sección.
 * Sin DNI: dato sensible de consulta restringida.
 * @var array  $alumnos          filas planas (matriculados aprobados)
 * @var array  $secciones        [{seccion_id, nivel_nombre, grado_nombre, seccion_nombre, n}]
 * @var int    $total
 * @var bool   $tieneOrdenMerito si hay un bimestre cerrado con ranking vigente
 * @var ?string $bimestre        nombre del bimestre del orden de mérito vigente
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
        <p class="text-sm text-muted">
            <?= $tieneOrdenMerito
                ? 'Orden de mérito vigente: ' . e($bimestre)
                : 'Aún no hay orden de mérito vigente (ningún bimestre cerrado).' ?>
        </p>
    </div>
</div>

<!-- Resultados (ocultos hasta buscar) -->
<div class="buscador-resultados" id="nomina-resultados" hidden>
    <?php foreach ($alumnos as $a): ?>
        <?php
        $nombre  = $a['apellido_paterno'] . ' ' . $a['apellido_materno'] . ', ' . $a['nombres'];
        $inicial = mb_strtoupper(mb_substr($a['apellido_paterno'] ?: $a['nombres'], 0, 1));
        $tutorLabel = match ($a['tutor_sexo'] ?? null) {
            'M'     => 'Tutor',
            'F'     => 'Tutora',
            default => 'Tutor(a)',
        };
        ?>
        <div class="buscador-item card nomina-fila" data-buscar="<?= e(mb_strtolower($nombre)) ?>" hidden>
            <div class="buscador-item__body">
                <div class="buscador-item__avatar"><?= e($inicial) ?></div>
                <div class="buscador-item__info">
                    <div class="buscador-item__nombre"><?= e($nombre) ?></div>
                    <div class="buscador-item__sub"><?= $tutorLabel ?>: <?= $a['tutor_nombre'] !== '' ? e($a['tutor_nombre']) : 'sin asignar' ?></div>
                    <div class="buscador-item__sub">Apoderado: <?= $a['apoderado_nombre'] !== '' ? e($a['apoderado_nombre']) : '—' ?></div>
                    <div class="buscador-item__sub">Cel: <?= $a['apoderado_telefono'] ? e($a['apoderado_telefono']) : '—' ?></div>
                </div>
                <div class="buscador-item__ubicacion">
                    <div class="buscador-item__lugar"><?= e($a['grado_nombre'] . ' ' . $a['seccion_nombre']) ?></div>
                    <div class="buscador-item__nivel"><?= e($a['nivel_nombre']) ?></div>
                    <?php if (!$tieneOrdenMerito): ?>
                        <div class="buscador-item__puesto buscador-item__puesto--vacio">Sin orden de mérito</div>
                    <?php elseif ($a['puesto'] !== null): ?>
                        <div class="buscador-item__puesto">Puesto <?= (int) $a['puesto'] ?>.° del grado</div>
                    <?php else: ?>
                        <div class="buscador-item__puesto buscador-item__puesto--vacio">Sin puesto aún</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
