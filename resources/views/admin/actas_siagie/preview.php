<?php
/**
 * Vista: Actas SIAGIE — previsualización (paso 2).
 *
 * @var string $token  identificador del job en sesión
 * @var string $nombre nombre del archivo subido
 * @var array  $r      resultado de LlenadorSiagie::analizar()
 */
$destino = $r['destino'];
$resumen = $r['resumen'];
$matching = $r['matching'] ?? null;
$roster       = $r['roster'];
$rosterOtras  = $r['roster_otras'] ?? [];
$gradoNum     = (int) $destino['grado_numero'];

// Enlaces al origen (para corregir la raíz en SIGA).
$urlConsulta = url('consulta-notas/' . (int) $destino['periodo_id'] . '/seccion/' . (int) $destino['seccion_id']);
$urlCurriculo = url('admin/curriculum');
$urlBuscar    = url('admin/buscar-estudiante');

// Clasificar las filas del matching para la UI.
$porResolver = [];   // sin_match | ambiguo → selector de identidad
$conflictos  = [];   // conflicto_codigo → enlace a corregir
if ($matching) {
    foreach ($matching['matches'] as $mm) {
        if (in_array($mm['estado'], ['sin_match', 'ambiguo'], true)) {
            $porResolver[] = $mm;
        } elseif ($mm['estado'] === 'conflicto_codigo') {
            $conflictos[] = $mm;
        }
    }
}
$sinFila = $matching['siga_sin_fila'] ?? [];
$totalCeldas = $resumen['nl'] + $resumen['conc'];
$emparejados = $resumen['match_codigo'] + $resumen['match_nombre'] + $resumen['match_manual'];

// Opciones del roster para cada selector de identidad. Dos grupos: esta
// sección y (si aplica) las otras secciones del grado (cambio de sección).
$rosterOpciones = '<optgroup label="' . e('Esta sección (' . $gradoNum . $destino['seccion_nombre'] . ')') . '">';
foreach ($roster as $e) {
    $etiqueta = trim($e['apellido_paterno'] . ' ' . $e['apellido_materno'] . ', ' . $e['nombres'])
        . ' — DNI ' . ($e['dni'] ?: 's/DNI');
    $rosterOpciones .= '<option value="' . (int) $e['estudiante_id'] . '">' . e($etiqueta) . '</option>';
}
$rosterOpciones .= '</optgroup>';
if ($rosterOtras !== []) {
    $rosterOpciones .= '<optgroup label="Otras secciones del grado">';
    foreach ($rosterOtras as $e) {
        $etiqueta = '[' . $gradoNum . $e['seccion_nombre'] . '] '
            . trim($e['apellido_paterno'] . ' ' . $e['apellido_materno'] . ', ' . $e['nombres'])
            . ' — DNI ' . ($e['dni'] ?: 's/DNI');
        $rosterOpciones .= '<option value="' . (int) $e['estudiante_id'] . '">' . e($etiqueta) . '</option>';
    }
    $rosterOpciones .= '</optgroup>';
}
?>

<div class="page-header">
    <div>
        <a href="<?= url('admin/actas-siagie') ?>" class="btn btn--secondary btn--sm">← Subir otra</a>
        <h1 class="page-title">Previsualización</h1>
        <p class="page-subtitle"><?= e($nombre) ?></p>
    </div>
</div>

<div class="actas-siagie">

    <!-- Resumen del destino y lo que se escribiría -->
    <div class="card mb-lg">
        <div class="card__header card__header--between">
            <h2 class="card__title"><?= e($destino['nivel_nombre']) ?> <?= (int) $destino['grado_numero'] ?><?= e($destino['seccion_nombre']) ?> — <?= e($destino['periodo_nombre']) ?></h2>
            <span class="badge badge--activo">bimestre cerrado</span>
        </div>
        <div class="card__body">
            <div class="actas-metricas">
                <div class="actas-metrica">
                    <span class="actas-metrica__num"><?= (int) $resumen['nl'] ?></span>
                    <span class="actas-metrica__lbl">notas (NL)</span>
                </div>
                <div class="actas-metrica">
                    <span class="actas-metrica__num"><?= (int) $resumen['conc'] ?></span>
                    <span class="actas-metrica__lbl">conclusiones</span>
                </div>
                <div class="actas-metrica">
                    <span class="actas-metrica__num"><?= $emparejados ?>/<?= (int) $resumen['estudiantes_excel'] ?></span>
                    <span class="actas-metrica__lbl">estudiantes emparejados</span>
                </div>
                <?php if ($resumen['advertencias'] > 0): ?>
                    <div class="actas-metrica actas-metrica--warn">
                        <span class="actas-metrica__num"><?= (int) $resumen['advertencias'] ?></span>
                        <span class="actas-metrica__lbl">advertencias</span>
                    </div>
                <?php endif; ?>
                <?php if ($resumen['blancos'] > 0): ?>
                    <div class="actas-metrica actas-metrica--muted">
                        <span class="actas-metrica__num"><?= (int) $resumen['blancos'] ?></span>
                        <span class="actas-metrica__lbl">celdas en blanco</span>
                    </div>
                <?php endif; ?>
                <?php if (($resumen['autorizadas'] ?? 0) > 0): ?>
                    <div class="actas-metrica actas-metrica--warn">
                        <span class="actas-metrica__num"><?= (int) $resumen['autorizadas'] ?></span>
                        <span class="actas-metrica__lbl">notas autorizadas (dirección)</span>
                    </div>
                <?php endif; ?>
                <?php if (($resumen['otra_seccion'] ?? 0) > 0): ?>
                    <div class="actas-metrica actas-metrica--warn">
                        <span class="actas-metrica__num"><?= (int) $resumen['otra_seccion'] ?></span>
                        <span class="actas-metrica__lbl">posible cambio de sección</span>
                    </div>
                <?php endif; ?>
            </div>
            <p class="text-muted">
                Emparejados por código <?= (int) $resumen['match_codigo'] ?>,
                por nombre <?= (int) $resumen['match_nombre'] ?><?php if ($resumen['match_manual'] > 0): ?>,
                resueltos a mano <?= (int) $resumen['match_manual'] ?><?php endif; ?>.
            </p>
        </div>
    </div>

    <form method="POST" action="<?= url('admin/actas-siagie/confirmar') ?>"
          onsubmit="return confirm('¿Generar el acta llenada? Se escribirán <?= $totalCeldas ?> celda(s). Esta acción no modifica tu archivo original: descargarás una copia llenada.');">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <!-- Resolución de identidad -->
        <?php if ($porResolver !== []): ?>
            <div class="card mb-lg">
                <div class="card__header card__header--between">
                    <h2 class="card__title">Estudiantes sin emparejar</h2>
                    <span class="badge badge--warning"><?= count($porResolver) ?> por resolver</span>
                </div>
                <div class="card__body">
                    <p>
                        Estas filas del Excel no coincidieron automáticamente con un estudiante de SIGA
                        (nombre distinto, homónimos, etc.). Elige el estudiante correcto por su <strong>DNI</strong>
                        o deja la fila en blanco. La nota siempre sale de SIGA; nunca se escribe a mano.
                        Si un alumno <strong>cambió de sección sin tramitarlo</strong>, aparece marcado abajo y
                        puedes elegirlo en el grupo <em>"Otras secciones del grado"</em> del selector.
                    </p>
                    <div class="tabla-responsive">
                        <table class="tabla-ranking">
                            <thead>
                                <tr>
                                    <th class="text-center">Fila</th>
                                    <th>Nombre en el Excel (SIAGIE)</th>
                                    <th>Estudiante de SIGA</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($porResolver as $mm): ?>
                                <tr>
                                    <td class="text-center"><?= (int) $mm['fila'] ?></td>
                                    <td>
                                        <?= e($mm['nombre']) ?>
                                        <?php if ($mm['detalle'] !== ''): ?>
                                            <span class="actas-hint"><?= e($mm['detalle']) ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($mm['otra_seccion'])): $os = $mm['otra_seccion']; ?>
                                            <span class="actas-cruce">
                                                <span class="badge badge--warning">posible cambio de sección</span>
                                                está en <strong><?= e($gradoNum . $os['seccion_nombre']) ?></strong>:
                                                <?= e($os['apellido_paterno'] . ' ' . $os['apellido_materno'] . ', ' . $os['nombres']) ?>
                                                (DNI <?= e($os['dni'] ?: 's/DNI') ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="resolucion[<?= (int) $mm['fila'] ?>]" class="form-select">
                                            <option value="0">— dejar en blanco —</option>
                                            <?= $rosterOpciones ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Conflictos de código (no se resuelven aquí: se corrigen en la matrícula) -->
        <?php if ($conflictos !== []): ?>
            <div class="card mb-lg">
                <div class="card__header card__header--between">
                    <h2 class="card__title">Conflictos de código</h2>
                    <span class="badge badge--error"><?= count($conflictos) ?></span>
                </div>
                <div class="card__body">
                    <p>
                        El código del Excel no coincide con el que SIGA ya tiene para ese nombre. Por seguridad
                        estas filas quedan en blanco: corrige el código en la matrícula del estudiante y vuelve a intentar.
                    </p>
                    <ul class="actas-lista">
                    <?php foreach ($conflictos as $mm): ?>
                        <li>Fila <?= (int) $mm['fila'] ?> — <?= e($mm['nombre']) ?>: <?= e($mm['detalle']) ?></li>
                    <?php endforeach; ?>
                    </ul>
                    <a href="<?= $urlBuscar ?>" class="btn btn--secondary btn--sm">Buscar estudiante para corregir →</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Enlaces al origen -->
        <?php if ($resumen['blancos'] > 0 || $resumen['advertencias'] > 0 || $sinFila !== []): ?>
            <div class="card mb-lg actas-origen">
                <div class="card__header">
                    <h2 class="card__title">¿Faltan notas o columnas?</h2>
                </div>
                <div class="card__body">
                    <p class="text-muted">
                        Las celdas en blanco no son un error del volcado: son datos que aún no están listos en SIGA.
                        Corrige la raíz y vuelve a subir el archivo.
                    </p>
                    <div class="actas-origen__links">
                        <a href="<?= $urlConsulta ?>" class="btn btn--secondary btn--sm">Consulta de notas de la sección →</a>
                        <a href="<?= $urlCurriculo ?>" class="btn btn--secondary btn--sm">Currículo (nombres de competencias) →</a>
                    </div>
                    <?php if ($sinFila !== []): ?>
                        <p class="actas-subtitulo">En SIGA pero sin fila en el Excel (<?= count($sinFila) ?>):</p>
                        <ul class="actas-lista">
                        <?php foreach ($sinFila as $e): ?>
                            <li><?= e($e['apellido_paterno'] . ' ' . $e['apellido_materno'] . ', ' . $e['nombres']) ?> (DNI <?= e($e['dni'] ?: 's/DNI') ?>)</li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reporte completo -->
        <div class="card mb-lg">
            <div class="card__header card__header--between">
                <h2 class="card__title">Reporte de análisis</h2>
                <a href="<?= url('admin/actas-siagie/reporte') ?>" class="btn btn--secondary btn--sm" data-descarga="<?= e('reporte_' . pathinfo($nombre, PATHINFO_FILENAME) . '.txt') ?>">Descargar .txt</a>
            </div>
            <div class="card__body">
                <details class="actas-reporte">
                    <summary>Ver reporte completo</summary>
                    <pre class="actas-reporte__pre"><?= e(implode("\n", $r['reporte'])) ?></pre>
                </details>
            </div>
        </div>

        <!-- Confirmar -->
        <div class="actas-acciones">
            <a href="<?= url('admin/actas-siagie') ?>" class="btn btn--secondary">Cancelar</a>
            <button type="submit" class="btn btn--primary"
                <?= $totalCeldas === 0 ? 'disabled' : '' ?>>
                <span class="btn-icon btn-icon--check" aria-hidden="true"></span>
                Confirmar y generar acta
            </button>
        </div>
        <?php if ($totalCeldas === 0): ?>
            <p class="text-muted text-center">No hay nada que escribir en este archivo.</p>
        <?php endif; ?>
    </form>

</div>
