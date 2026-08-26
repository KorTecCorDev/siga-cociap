<?php
/**
 * Barra de navegacion de la consulta de calificaciones (26/08/2026).
 *
 * Dos piezas que SIEMPRE van juntas y en este orden:
 *
 *   1. La card del SELECTOR DE BIMESTRE — el ambito, comun a las tres pestañas.
 *   2. El conmutador de EJES — Secciones | Docentes | Criterios.
 *
 * Van en el mismo partial porque salen del MISMO mapa de rutas (`$rutas`): en dos
 * partials ese mapa se escribiria dos veces y acabaria divergiendo.
 *
 * 🔴 LA CARD VA ENCIMA DE LAS PESTAÑAS A PROPOSITO. Debajo se leia como contenido
 * de la pestaña Secciones, cuando manda sobre las tres. Arriba = ambito global;
 * lo que quede debajo de la barra pertenece a la pestaña (en Criterios, su card
 * de filtros).
 *
 * @var array      $periodos   listarPeriodos() del controlador
 * @var array|null $periodo    null SOLO en el indice sin seleccion
 * @var string     $ejeActivo  'secciones' | 'docentes' | 'criterios'
 */
$pid = (int) ($periodo['id'] ?? 0);

// Mapa unico: de aqui salen los tres enlaces Y la accion del formulario.
$rutas = [
    'secciones' => 'consulta-notas',
    'docentes'  => 'consulta-notas/' . $pid . '/docentes',
    'criterios' => 'consulta-notas/' . $pid . '/criterios',
];
$rotulos = [
    'secciones' => 'Secciones',
    'docentes'  => 'Docentes',
    'criterios' => 'Criterios',
];

// El form apunta a la pestaña ACTIVA: cambiar de bimestre nunca cambia de eje.
// En Secciones `periodo_id` es el parametro real; en las otras dos viaja como
// query sobre la ruta vieja y el controlador redirige (saltarDePeriodo).
$accion = url($rutas[$ejeActivo]);
?>

<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= $accion ?>">
            <label class="form-label" for="periodo_id">Periodo / Bimestre</label>
            <?php // Auto-aplica al cambiar, la convencion del repo (indice,
                  // /admin/control, /admin/conducta y 9 vistas mas).
                  //
                  // ⚠️ Elegir el bimestre QUE YA ESTA seleccionado no dispara
                  // `change`, asi que el form no se envia. Es lo que permite que
                  // este selector viva FUERA del formulario de filtros de
                  // Criterios sin limpiarlos por accidente. ?>
            <select name="periodo_id" id="periodo_id" class="form-input" onchange="this.form.submit()">
                <?php if ($periodo === null): ?>
                    <?php // Solo en el indice sin seleccion: en Docentes y Criterios el
                          // periodo siempre existe (notFound si no) y seria opcion muerta. ?>
                    <option value="">&mdash; Seleccionar periodo &mdash;</option>
                <?php endif; ?>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $pid === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['nombre_display']) ?> <?= e($p['anio']) ?>
                        (<?= $p['estado'] === 'activo' ? 'activo' : 'cerrado' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php // Sin JS no hay `onchange`. Criterios funcionaba sin JS porque su
                  // select vivia en el form de filtros, que tiene boton Aplicar; al
                  // sacarlo aqui, este boton conserva esa garantia. ?>
            <noscript>
                <button type="submit" class="btn btn--secondary btn--sm">Cambiar</button>
            </noscript>
        </form>
    </div>
</div>

<?php // Las pestañas solo con periodo: sin id no hay URL para los otros dos ejes.
      // La card de arriba SI se pinta — es el control que hace falta para elegirlo. ?>
<?php if ($pid): ?>
    <nav class="consulta-ejes" aria-label="Ejes de consulta">
        <?php foreach ($rutas as $clave => $ruta): ?>
            <?php
            $activo = ($clave === $ejeActivo);
            // Secciones lleva el periodo en la QUERY; las otras dos, en la ruta.
            $href   = url($ruta . ($clave === 'secciones' ? '?periodo_id=' . $pid : ''));
            ?>
            <a class="consulta-eje<?= $activo ? ' consulta-eje--activo' : '' ?>"
               href="<?= $href ?>"<?= $activo ? ' aria-current="page"' : '' ?>><?= e($rotulos[$clave]) ?></a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
