<?php
/**
 * Constancia de traslado — documento oficial A4 portrait (layout print).
 *
 * @var array      $traslado
 * @var array      $institucion   config('institucion_datos')
 * @var string     $codModular    código modular según el nivel del estudiante
 * @var string     $motivoLabel
 * @var array|null $directorEbr   { nombre_completo, sexo, firma_path }
 */
$inst = $institucion;

$cargoDirector = match($directorEbr['sexo'] ?? null) {
    'F'     => 'Directora de Educación Básica Regular',
    'M'     => 'Director de Educación Básica Regular',
    default => 'Director(a) de Educación Básica Regular',
};
$tituloSuscribe = match($directorEbr['sexo'] ?? null) {
    'F'     => 'La Directora',
    'M'     => 'El Director',
    default => 'El(La) Director(a)',
};

$gradoSeccion = trim(($traslado['grado_nombre'] ?? '') . ' "' . ($traslado['seccion_nombre'] ?? '') . '"');
$lugar        = $inst['lugar'] ?? 'Huaraz';
$anulada      = ($traslado['estado'] ?? '') === 'anulado';
?>

<article class="constancia">

    <?php if ($anulada): ?>
        <div class="constancia-anulada-sello">ANULADA</div>
    <?php endif; ?>

    <!-- ── Membrete ── -->
    <div class="constancia-lema"><?= e($traslado['lema_oficial'] ?? '') ?></div>

    <header class="constancia-header">
        <div class="constancia-header__logo">
            <img src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        </div>
        <div class="constancia-header__centro">
            <div class="constancia-header__rector"><?= e($inst['ente_rector'] ?? '') ?></div>
            <div class="constancia-header__colegio"><?= e($inst['nombre_oficial'] ?? '') ?></div>
            <?php if (!empty($inst['eslogan'])): ?>
                <div class="constancia-header__eslogan">«<?= e($inst['eslogan']) ?>»</div>
            <?php endif; ?>
            <div class="constancia-header__datos">
                Código Modular: <?= e($codModular) ?> &middot; Código de Local: <?= e($inst['codigo_local'] ?? '') ?>
            </div>
            <?php if (!empty($inst['resoluciones'])): ?>
                <div class="constancia-header__datos"><?= e($inst['resoluciones']) ?></div>
            <?php endif; ?>
        </div>
    </header>

    <hr class="constancia-regla">

    <!-- ── Título ── -->
    <h1 class="constancia-titulo">CONSTANCIA DE TRASLADO</h1>
    <p class="constancia-numero-oficial"><?= e($traslado['numero_constancia']) ?></p>

    <!-- ── Cuerpo ── -->
    <div class="constancia-cuerpo">
        <p>
            <?= $tituloSuscribe ?> de la <?= e($inst['nombre_oficial'] ?? '') ?>, que suscribe,
        </p>
        <p class="constancia-hace-constar">HACE CONSTAR QUE:</p>
        <p>
            El(La) estudiante <strong><?= e($traslado['estudiante_nombre']) ?></strong>,
            identificado(a) con DNI N° <strong><?= e($traslado['dni']) ?></strong>,
            estuvo matriculado(a) en el <strong><?= e($gradoSeccion) ?></strong>
            del nivel de <strong><?= e($traslado['nivel_nombre'] ?? '') ?></strong>
            durante el Año Académico <strong><?= e((string) $traslado['anio']) ?></strong><?php
            if (!empty($traslado['periodo_nombre'])): ?>, hasta el <?= e($traslado['periodo_nombre']) ?><?php endif; ?>.
        </p>
        <p>
            Se otorga el presente <strong>TRASLADO</strong> a la Institución Educativa
            <strong><?= e($traslado['ie_destino_nombre']) ?></strong>,
            con Código Modular <strong><?= e($traslado['ie_destino_codigo_modular']) ?></strong><?php
            if (!empty($traslado['ie_destino_ugel'])): ?>, jurisdicción de <?= e($traslado['ie_destino_ugel']) ?><?php endif; ?><?php
            if (!empty($traslado['ie_destino_ubicacion'])): ?> (<?= e($traslado['ie_destino_ubicacion']) ?>)<?php endif; ?>,
            por <strong><?= e(mb_strtolower($motivoLabel)) ?></strong><?php
            if (!empty($traslado['motivo_detalle'])): ?>: <?= e($traslado['motivo_detalle']) ?><?php endif; ?>.
        </p>
        <?php if (!empty($traslado['situacion_academica'])): ?>
        <p><?= e($traslado['situacion_academica']) ?></p>
        <?php endif; ?>
        <p>
            Se expide la presente constancia<?php
            if (!empty($traslado['solicitante_nombre'])): ?> a solicitud de
            <strong><?= e($traslado['solicitante_nombre']) ?></strong><?php
                if (!empty($traslado['solicitante_parentesco'])): ?> (<?= e($traslado['solicitante_parentesco']) ?>)<?php endif; ?><?php
            else: ?> a solicitud del interesado<?php endif; ?>,
            para los fines del traslado de matrícula en el SIAGIE.
        </p>
        <?php if (!empty($traslado['observaciones'])): ?>
        <p class="constancia-obs"><em>Observaciones:</em> <?= e($traslado['observaciones']) ?></p>
        <?php endif; ?>
    </div>

    <p class="constancia-lugar-fecha">
        <?= e($lugar) ?>, <?= fecha_es($traslado['fecha_constancia']) ?>.
    </p>

    <!-- ── Firma del Director EBR ── -->
    <div class="constancia-firma">
        <div class="constancia-firma__espacio">
            <?php if (!$anulada && !empty($directorEbr['firma_path'])): ?>
                <img src="<?= url($directorEbr['firma_path']) ?>" alt="" aria-hidden="true"
                     class="constancia-firma__img">
            <?php endif; ?>
        </div>
        <div class="constancia-firma__linea"></div>
        <?php if (!empty($directorEbr['nombre_completo'])): ?>
            <div class="constancia-firma__nombre"><?= e($directorEbr['nombre_completo']) ?></div>
        <?php endif; ?>
        <div class="constancia-firma__cargo"><?= $cargoDirector ?></div>
    </div>

    <!-- ── Pie institucional + marca SIGA ── -->
    <footer class="constancia-pie">
        <div class="constancia-pie__inst">
            <?= e($inst['direccion'] ?? '') ?><?php if (!empty($inst['ubicacion'])): ?> &middot; <?= e($inst['ubicacion']) ?><?php endif; ?>
            <?php if (!empty($inst['telefonos'])): ?><br>Tel. <?= e($inst['telefonos']) ?><?php endif; ?>
        </div>
        <div class="constancia-pie__siga">
            Documento elaborado e impreso por el SIGA-COCIAP &middot; <?= date('d/m/Y H:i') ?>
        </div>
    </footer>

</article>
