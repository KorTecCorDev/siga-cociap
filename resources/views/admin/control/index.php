<?php
/**
 * Vista: Centro de Control Operativo — detección de inconsistencias.
 *
 * @var array       $periodos          [{id, nombre_display, anio, ...}]
 * @var array|null  $periodo           periodo seleccionado
 * @var array       $chequeos          ['empates'=>{titulo,severidad,accion,items[],...}, ...]
 * @var int         $totalIncidencias
 * @var string      $estadoBoleta      'registro' | 'borrador' | 'oficial'
 * @var array       $publicacion       compuerta (044): una fila por nivel
 *                                     [{nivel_id, nivel_nombre, publica_en,
 *                                       suspendida_en, despublicada_en,
 *                                       motivo_despublicacion, estado}]
 * @var bool        $puedePublicar     admin/RA si; directores solo miran
 */
$badgePublicacion = static fn(string $est): string => match ($est) {
    'publicado'    => 'badge--activo',
    'programado'   => 'badge--info',
    'suspendido'   => 'badge--warning',
    'despublicado' => 'badge--error',
    default        => 'badge--info',
};
$rotuloPublicacion = static fn(string $est): string => match ($est) {
    'publicado'    => 'Publicado',
    'programado'   => 'Programado',
    'suspendido'   => 'Suspendido por reapertura',
    'despublicado' => 'Retirado',
    default        => 'Sin publicar',
};
$fechaLarga = static fn(?string $f): string =>
    $f ? date('d/m/Y', strtotime($f)) . ' a las ' . date('H:i', strtotime($f)) : '';

$badgeSeveridad = static fn(string $sev): string =>
    $sev === 'critico' ? 'badge--error' : 'badge--warning';
?>

<div class="page-header">
    <div>
        <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
        <h1 class="page-title">Centro de Control</h1>
        <p class="page-subtitle">Inconsistencias operativas pendientes de corregir</p>
    </div>
    <?php if (!empty($periodos)): ?>
        <form method="GET" action="<?= url('admin/control') ?>" class="control-selector">
            <label for="periodo_id" class="form-label">Periodo</label>
            <select name="periodo_id" id="periodo_id" class="form-select"
                    onchange="this.form.submit()">
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= ($periodo && (int) $periodo['id'] === (int) $p['id']) ? 'selected' : '' ?>>
                        <?= e($p['nombre_display'] . ' — ' . $p['anio']) ?>
                        <?= $p['estado'] === 'activo' ? '(activo)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if (!$periodo): ?>
    <div class="empty-state">
        <p>No hay periodos disponibles para analizar.</p>
    </div>
<?php else: ?>

    <?php if ($totalIncidencias === 0): ?>
        <div class="control-resumen control-resumen--ok">
            <span class="control-resumen__icono">✓</span>
            <div>
                <strong>Todo en orden.</strong>
                No se detectaron inconsistencias para <?= e($periodo['nombre_display']) ?>.
            </div>
        </div>
    <?php else: ?>
        <div class="control-resumen control-resumen--alerta">
            <span class="control-resumen__icono">⚠</span>
            <div>
                <strong><?= $totalIncidencias ?></strong>
                <?= $totalIncidencias === 1 ? 'inconsistencia detectada' : 'inconsistencias detectadas' ?>
                en <?= e($periodo['nombre_display']) ?>. Revisá cada bloque y corregí en su módulo.
            </div>
        </div>
    <?php endif; ?>

    <!-- Cierre de bimestre — Hito A (aprobar boletas -> borrador para docentes) -->
    <div class="card mb-lg">
        <div class="card__header card__header--between">
            <h2 class="card__title">Cierre de bimestre — boletas</h2>
            <?php if ($estadoBoleta === 'oficial'): ?>
                <span class="badge badge--activo">Oficial (bimestre cerrado)</span>
            <?php elseif ($estadoBoleta === 'borrador'): ?>
                <span class="badge badge--warning">Boletas en borrador</span>
            <?php else: ?>
                <span class="badge badge--info">En registro</span>
            <?php endif; ?>
        </div>
        <div class="card__body">
            <?php if ($estadoBoleta === 'oficial'): ?>
                <p class="text-muted">
                    El bimestre está cerrado y las boletas son <strong>oficiales</strong>.
                    Cerrar <strong>no</strong> las muestra a las familias: eso se decide abajo,
                    en <strong>Publicación de boletas</strong>. Para corregir notas, usa
                    Rectificación de notas.
                </p>
            <?php elseif ($estadoBoleta === 'borrador'): ?>
                <p>
                    Las boletas están en <strong>BORRADOR</strong>: los docentes ya ven la vista
                    previa en su nómina. Cuando den el visto bueno, <strong>cierra el bimestre</strong>
                    desde Año Académico para oficializarlas. Las familias seguirán sin verlas
                    hasta que las publiques abajo.
                </p>
                <form method="POST"
                      action="<?= url('admin/control/' . (int) $periodo['id'] . '/anular-aprobacion') ?>"
                      onsubmit="return confirm('¿Revertir la aprobación? Las boletas borrador dejarán de mostrarse a los docentes.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--secondary">
                        <span class="btn-icon btn-icon--back" aria-hidden="true"></span>
                        Revertir aprobación
                    </button>
                </form>
            <?php else: /* en registro */ ?>
                <?php $sinBloquear = count($chequeos['competencias']['items'] ?? []); ?>
                <p>
                    Al <strong>aprobar el bimestre</strong> se generan las <strong>boletas borrador</strong>
                    para que los docentes las revisen. Las competencias que sigan pendientes se
                    bloquearán automáticamente (quedarán como Incidencias).
                </p>
                <?php if ($sinBloquear > 0): ?>
                    <p class="text-danger">
                        ⚠ Hay <?= $sinBloquear ?> sección(es) con competencias sin bloquear: se forzarán al aprobar.
                    </p>
                <?php endif; ?>
                <form method="POST"
                      action="<?= url('admin/control/' . (int) $periodo['id'] . '/aprobar-bimestre') ?>"
                      onsubmit="return confirm('¿Bloquear y aprobar el bimestre? Se generan las boletas borrador y se fuerza el bloqueo de lo pendiente.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--primary">
                        <span class="btn-icon btn-icon--check" aria-hidden="true"></span>
                        Bloquear y aprobar el bimestre
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!--
        Compuerta de publicación (044) — tercer paso del cierre, después del
        Hito A y del cierre del bimestre. Se publica POR NIVEL porque las
        boletas se entregan en reuniones y primaria suele ir un día antes.
    -->
    <div class="card mb-lg">
        <div class="card__header card__header--between">
            <h2 class="card__title">Publicación de boletas a las familias</h2>
            <?php if (!$puedePublicar): ?>
                <span class="badge badge--info">Solo lectura</span>
            <?php endif; ?>
        </div>
        <div class="card__body">
            <?php if ($periodo['estado'] !== 'cerrado'): ?>
                <p class="text-muted">
                    Las boletas se publican una vez que el bimestre está <strong>cerrado</strong>.
                    Cierra <?= e($periodo['nombre_display']) ?> desde Año Académico y vuelve aquí
                    para entregarlas a las familias.
                </p>
            <?php else: ?>
                <p>
                    Cerrar el bimestre <strong>no</strong> muestra las boletas a las familias.
                    Publica cada nivel el día de su reunión de entrega, o
                    <strong>prográmalo</strong> con fecha y hora exactas.
                    Esto solo afecta el acceso <strong>en línea</strong> de las familias
                    (enlace, QR y portal): la impresión masiva de Registro Académico
                    funciona igual, publicada o no.
                </p>

                <?php if (!$puedePublicar): ?>
                    <p class="text-muted">
                        Tu rol puede ver el estado pero no publicar. La publicación la operan
                        Registro Académico y administración.
                    </p>
                <?php endif; ?>

                <div class="publicacion-niveles">
                    <?php foreach ($publicacion as $pn): ?>
                        <?php $est = (string) $pn['estado']; ?>
                        <div class="publicacion-nivel">
                            <div class="publicacion-nivel__head">
                                <strong><?= e($pn['nivel_nombre']) ?></strong>
                                <span class="badge <?= $badgePublicacion($est) ?>">
                                    <?= e($rotuloPublicacion($est)) ?>
                                </span>
                            </div>

                            <p class="publicacion-nivel__estado">
                                <?php if ($est === 'publicado'): ?>
                                    Visible para las familias desde el
                                    <?= e($fechaLarga($pn['publica_en'])) ?>.
                                <?php elseif ($est === 'programado'): ?>
                                    Se publicará solo el <?= e($fechaLarga($pn['publica_en'])) ?>.
                                    Hasta entonces las familias no ven nada.
                                <?php elseif ($est === 'suspendido'): ?>
                                    Oculta porque el bimestre fue reabierto. Al volver a cerrarlo
                                    se restaura sola, sin publicar de nuevo.
                                <?php elseif ($est === 'despublicado'): ?>
                                    Retirada de circulación.
                                    <?php if (!empty($pn['motivo_despublicacion'])): ?>
                                        Motivo: <?= e($pn['motivo_despublicacion']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Las familias de este nivel todavía no ven sus boletas.
                                <?php endif; ?>
                            </p>

                            <?php if ($puedePublicar): ?>
                                <div class="publicacion-nivel__acciones">
                                    <?php if ($est !== 'publicado'): ?>
                                        <form method="POST"
                                              action="<?= url('admin/control/' . (int) $periodo['id'] . '/publicar') ?>"
                                              onsubmit="return confirm('¿Publicar ahora las boletas de <?= e($pn['nivel_nombre']) ?>? Las familias podrán verlas de inmediato.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="nivel_id" value="<?= (int) $pn['nivel_id'] ?>">
                                            <button type="submit" class="btn btn--primary btn--sm">
                                                <span class="btn-icon btn-icon--check" aria-hidden="true"></span>
                                                Publicar ahora
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST"
                                          action="<?= url('admin/control/' . (int) $periodo['id'] . '/programar') ?>"
                                          class="publicacion-nivel__programar">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="nivel_id" value="<?= (int) $pn['nivel_id'] ?>">
                                        <label class="form-label" for="publica_en_<?= (int) $pn['nivel_id'] ?>">
                                            Programar para
                                        </label>
                                        <input type="datetime-local"
                                               id="publica_en_<?= (int) $pn['nivel_id'] ?>"
                                               name="publica_en"
                                               class="form-input"
                                               required>
                                        <button type="submit" class="btn btn--secondary btn--sm">
                                            Programar
                                        </button>
                                    </form>

                                    <?php if ($est === 'publicado' || $est === 'programado'): ?>
                                        <form method="POST"
                                              action="<?= url('admin/control/' . (int) $periodo['id'] . '/despublicar') ?>"
                                              class="publicacion-nivel__retirar"
                                              onsubmit="return confirm('¿Retirar las boletas de <?= e($pn['nivel_nombre']) ?>? Las familias dejarán de verlas. Volver a cerrar el bimestre NO las restaura: hay que publicarlas de nuevo a mano.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="nivel_id" value="<?= (int) $pn['nivel_id'] ?>">
                                            <label class="form-label" for="motivo_<?= (int) $pn['nivel_id'] ?>">
                                                Motivo para retirarlas
                                            </label>
                                            <input type="text"
                                                   id="motivo_<?= (int) $pn['nivel_id'] ?>"
                                                   name="motivo"
                                                   class="form-input"
                                                   minlength="10"
                                                   maxlength="500"
                                                   placeholder="Mínimo 10 caracteres"
                                                   required>
                                            <button type="submit" class="btn btn--danger btn--sm">
                                                Retirar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- F5: Incidencias del cierre forzado (etiqueta neutral) -->
    <?php if ($estadoBoleta !== 'registro'): $res = $incidencias['resumen']; ?>
        <div class="card mb-lg">
            <div class="card__header card__header--between">
                <h2 class="card__title">Incidencias del cierre</h2>
                <?php if ($res['competencias'] === 0): ?>
                    <span class="badge badge--activo">✓ Sin incidencias</span>
                <?php else: ?>
                    <span class="badge badge--warning">
                        <?= (int) $res['competencias'] ?> forzada<?= $res['competencias'] === 1 ? '' : 's' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card__body">
                <?php if ($res['competencias'] === 0): ?>
                    <p class="text-muted">
                        Ningún docente quedó forzado en este bimestre: todas las competencias
                        se bloquearon a tiempo antes de aprobar las boletas.
                    </p>
                <?php else: ?>
                    <p>
                        Competencias que el cierre bloqueó automáticamente porque el docente no las
                        había bloqueado al aprobar el bimestre.
                        <strong><?= (int) $res['competencias'] ?></strong> competencia(s) en
                        <strong><?= (int) $res['cargas'] ?></strong> carga(s) de
                        <strong><?= (int) $res['docentes'] ?></strong> docente(s);
                        <strong><?= (int) $res['sin_avance'] ?></strong> sin ningún criterio registrado.
                    </p>
                    <div class="tabla-responsive">
                    <table class="tabla-ranking">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th class="text-center">Cargas</th>
                                <th class="text-center">Competencias forzadas</th>
                                <th class="text-center">Sin avance</th>
                                <th class="text-center">Forzado el</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($incidencias['docentes'] as $d): ?>
                            <tr>
                                <td><?= e($d['nombre_completo']) ?></td>
                                <td class="text-center"><?= (int) $d['n_cargas'] ?></td>
                                <td class="text-center"><?= (int) $d['n_competencias'] ?></td>
                                <td class="text-center">
                                    <?php if ((int) $d['sin_avance'] > 0): ?>
                                        <span class="badge badge--warning"><?= (int) $d['sin_avance'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= $d['forzado_en'] ? e(date('d/m/Y H:i', strtotime((string) $d['forzado_en']))) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($chequeos as $clave => $c): $n = count($c['items']); ?>
        <div class="card mb-lg">
            <div class="card__header card__header--between">
                <h2 class="card__title"><?= e($c['titulo']) ?></h2>
                <?php if ($n === 0): ?>
                    <span class="badge badge--activo">✓ En orden</span>
                <?php else: ?>
                    <span class="badge <?= $badgeSeveridad($c['severidad']) ?>">
                        <?= $n ?> <?= $n === 1 ? 'caso' : 'casos' ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($n === 0): ?>
                <div class="card__body">
                    <p class="text-muted">Sin pendientes en este chequeo.</p>
                </div>
            <?php else: ?>
                <div class="tabla-responsive">
                <table class="tabla-ranking">
                    <?php if ($clave === 'empates'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Grupos en empate</th><th class="text-center">Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= (int) $it['n_grupos'] ?></td>
                                <td class="text-center">
                                    <a class="btn btn--primary btn--sm"
                                       href="<?= url('director/orden-merito/' . (int) $periodo['id'] . '/desempate/' . (int) $it['grado_id']) ?>">
                                        Resolver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php elseif ($clave === 'competencias'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Sección</th><th class="text-center">Competencias sin bloquear</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= e($it['seccion_nombre']) ?></td>
                                <td class="text-center"><?= (int) $it['n_competencias'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php elseif ($clave === 'tutores'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Sección</th><th class="text-center">Matriculados</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= e($it['seccion_nombre']) ?></td>
                                <td class="text-center"><?= (int) ($it['total_matriculados'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php elseif ($clave === 'fantasmas'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Sección</th><th>Área</th><th>Competencia</th><th>Docente</th><th class="text-center">Notas</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= e($it['seccion_nombre']) ?></td>
                                <td><?= e($it['area_nombre']) ?></td>
                                <td><?= e(trim(($it['competencia_codigo'] ?? '') . ' ' . $it['competencia_nombre'])) ?></td>
                                <td><?= e($it['docente'] ?? '—') ?></td>
                                <td class="text-center"><?= (int) $it['n_calificaciones'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php else: /* matriculas */ ?>
                        <thead><tr><th>Estudiante</th><th>Grado / Sección</th><th class="text-center">Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['apellido_paterno'] . ' ' . $it['apellido_materno'] . ', ' . $it['nombres']) ?></td>
                                <td><?= e($it['grado_nombre'] . ' ' . $it['seccion_nombre']) ?></td>
                                <td class="text-center"><span class="badge badge--warning"><?= e($it['estado']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
                </div>

                <?php if (!empty($c['accion_url'])): ?>
                    <div class="card__footer">
                        <a class="btn btn--secondary btn--sm" href="<?= e($c['accion_url']) ?>">
                            <?= e($c['accion']) ?> →
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
