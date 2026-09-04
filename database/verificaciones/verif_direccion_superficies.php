<?php

/**
 * Verificación de las FASES 4 a 7.
 * Incluye render REAL de las vistas nuevas contra datos de la base: es lo único
 * que atrapa una clave inexistente o una variable no pasada.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEW_PATH', ROOT_PATH . '/resources/views');

require ROOT_PATH . '/app/Helpers/helpers.php';
spl_autoload_register(function (string $c): void {
    foreach (['Core\\' => '/core/', 'App\\Models\\' => '/app/Models/'] as $p => $b) {
        if (str_starts_with($c, $p)) {
            $f = ROOT_PATH . $b . str_replace('\\', '/', substr($c, strlen($p))) . '.php';
            if (is_file($f)) { require $f; }
        }
    }
});

$ok  = true;
$chk = function (string $t, bool $c, string $extra = '') use (&$ok) {
    printf("  [%s] %s%s\n", $c ? 'OK ' : 'FAIL', $t, $extra ? " — $extra" : '');
    $ok = $ok && $c;
};
$leer = fn(string $r): string => file_get_contents(ROOT_PATH . $r);

// ── FASE 4 ───────────────────────────────────────────────────────
echo "FASE 4 — matriculas en lectura\n";
$mc = $leer('/app/Controllers/Matricula/MatriculaController.php');
$chk('el constructor deja entrar a los directores', str_contains($mc, '...ROLES_DIRECCION'));

foreach (['create', 'store', 'apoderado', 'storeApoderado', 'documentos', 'storeDocumentos', 'notasExternas', 'storeNotasExternas'] as $m) {
    $patron = '/public function ' . preg_quote($m, '/') . '\([^)]*\)(?:\s*:\s*\w+)?\s*\r?\n\s*\{\s*\r?\n\s*\$this->requireRole\(self::ROLES_MATRICULAN\);/';
    $chk("$m() blindado con ROLES_MATRICULAN", (bool) preg_match($patron, $mc));
}
preg_match('/private const ROLES_MATRICULAN = (\[.*?\]);/s', $mc, $m);
$chk('ROLES_MATRICULAN conserva EXACTAMENTE los roles de antes',
    isset($m[1]) && eval("return {$m[1]};") === ['admin', 'registro_academico', 'secretaria_academica', 'secretaria_administrativa']);

$bc = $leer('/app/Controllers/Boleta/BoletaController.php');
$chk('la boleta de gestion admite a los directores (2 metodos)',
    substr_count($bc, "'secretaria_administrativa', ...ROLES_DIRECCION") === 2);
$chk('resumenImprimir admite a los directores',
    (bool) preg_match('/resumenImprimir.*?ROLES_DIRECCION/s', $mc));

// ── FASE 5 ───────────────────────────────────────────────────────
echo "\nFASE 5 — navegacion\n";
$ac = $leer('/app/Controllers/Auth/AuthController.php');
$dc = $leer('/app/Controllers/DashboardController.php');
$chk('AuthController ya no manda a los directores a /director/anios',
    !preg_match("/'director_\w+'\s*=>\s*url\('director\/anios'\)/", $ac));
$chk('DashboardController tampoco',
    !preg_match("/'director_\w+'\s*=>\s*url\('director\/anios'\)/", $dc));
$chk('los directores entran al grupo con cards', str_contains($dc, 'rolesConCards'));

// Cards por rol, evaluando el arreglo real de la vista.
$src = $leer('/resources/views/dashboard/index.php');
preg_match('/\$grupos = (\[.*?\n\];)/s', $src, $mm);
$grupos = eval('return ' . rtrim($mm[1], ';') . ';');
$cards = [];
foreach ($grupos as $g) { foreach ($g as $mod) { $cards[$mod['url']] = $mod['roles']; } }

// La 11.a entro el 24/08 con el explorador de criterios. La asercion compara la
// LISTA EXACTA, no el numero: si manana se cuela una card de escritura, o falta
// una de estas, sigue fallando igual.
$esperadasDirector = [
    'director/anios', 'director/cargas', 'matriculas', 'admin/buscar-estudiante',
    'admin/control', 'consulta-notas', 'consulta-notas/criterios', 'admin/cuadros',
    'director/bloqueos', 'director/orden-merito', 'director/ranking-seccion',
];
foreach (ROLES_DIRECCION as $rol) {
    $suyas = array_keys(array_filter($cards, fn($r) => in_array($rol, $r, true)));
    sort($suyas); $esp = $esperadasDirector; sort($esp);
    $chk("$rol ve las 11 cards previstas", $suyas === $esp, count($suyas) . ' cards');
}
$chk('ninguna card de ESCRITURA se le coló al director',
    empty(array_intersect(array_keys(array_filter($cards, fn($r) => in_array('director_ebr', $r, true))),
        ['admin/conducta', 'admin/asistencia', 'rectificaciones', 'admin/boletas-publicas',
         'admin/actas-siagie', 'admin/usuarios', 'admin/director-ebr', 'admin/secciones', 'admin/curriculum'])));

// ── Un glifo, un concepto ─────────────────────────────────────────────
// El wayfinding del sistema (docs/modulos/ui.md) fija el color por concepto
// "para que ubiquen el acceso sin leer". El GLIFO manda igual: si dos cards
// comparten icono, ese atajo deja de funcionar. Pasaba con tres pares —la
// medalla significaba a la vez "merito" y "cuadros", y encima contradecia al
// panel del docente, que ya la usa como wayfinding de merito—.
//
// La forma en que esto regresa es que una card NUEVA reuse un icono existente
// porque ninguno le pega; nada avisa, y el dashboard queda con dos glifos
// iguales.
$iconos = [];
foreach ($grupos as $g) { foreach ($g as $mod) { $iconos[] = $mod['icon']; } }

// La unica pareja admitida hoy: no hay icono con el que sustituir a ninguna de
// las dos. Al resolverla, quitarla de aqui (y entonces la lista queda vacia).
$duplicadosOk = ['users-group-rounded.svg'];
$repetidos = array_keys(array_filter(
    array_count_values($iconos),
    fn(int $n, string $i): bool => $n > 1 && !in_array($i, $duplicadosOk, true),
    ARRAY_FILTER_USE_BOTH
));
$chk('ninguna card del dashboard comparte icono',
    empty($repetidos),
    $repetidos ? implode(', ', $repetidos) : count($iconos) . ' cards, '
        . count(array_unique($iconos)) . ' iconos distintos');

// Un icono mal escrito NO da error: pinta un <img> roto que nadie ve hasta
// abrir el dashboard.
$sinArchivo = array_values(array_filter(
    array_unique($iconos),
    fn(string $i): bool => !file_exists(ROOT_PATH . '/public/assets/icons/' . $i)
));
$chk('todos los iconos del dashboard existen en disco',
    empty($sinArchivo),
    $sinArchivo ? implode(', ', $sinArchivo) : count(array_unique($iconos)) . ' archivo(s)');

// ── FASE 6 ───────────────────────────────────────────────────────
echo "\nFASE 6 — superficies nuevas de consulta\n";
$rutas = $leer('/routes/web.php');
foreach ([
    '/consulta-notas/{periodo_id}/seccion/{seccion_id}/asistencia',
    '/consulta-notas/{periodo_id}/docentes',
    '/consulta-notas/{periodo_id}/docente/{docente_id}',
    '/director/cargas/seccion/{seccion_id}/horario',
    '/admin/cuadros',
    '/admin/cuadros/imprimir',
    '/consulta-notas/{periodo_id}/criterios',
    '/consulta-notas/{periodo_id}/criterios/imprimir',
    '/consulta-notas/criterios',
] as $r) {
    $chk("ruta registrada: $r", str_contains($rutas, "'$r'"));
}
// La de 5 segmentos ANTES que la de 4 (el router ancla por orden).
$chk('el horario por seccion se registra ANTES que /seccion/{id}',
    strpos($rutas, "'/director/cargas/seccion/{seccion_id}/horario'")
    < strpos($rutas, "'/director/cargas/seccion/{seccion_id}'"));

// El literal de 2 segmentos y el /imprimir van ANTES que el patron que podria
// tragarselos (el router ancla por orden de registro).
$chk('/consulta-notas/criterios se registra ANTES que {periodo_id}/criterios',
    strpos($rutas, "'/consulta-notas/criterios'")
    < strpos($rutas, "'/consulta-notas/{periodo_id}/criterios'"));
$chk('/criterios/imprimir se registra ANTES que /criterios',
    strpos($rutas, "'/consulta-notas/{periodo_id}/criterios/imprimir'")
    < strpos($rutas, "'/consulta-notas/{periodo_id}/criterios'"));
$chk('/admin/cuadros/imprimir se registra ANTES que /admin/cuadros',
    strpos($rutas, "'/admin/cuadros/imprimir'") < strpos($rutas, "'/admin/cuadros'"));

// El imprimible NO puede reusar el arbol de la pantalla: un <details> cerrado
// no imprime su contenido.
//
// Se miran los TOKENS, no el texto: el docblock de la vista NOMBRA la etiqueta
// a proposito (documenta por que no la usa), y un str_contains daria un rojo
// falso. Solo cuenta el HTML que la vista emite (T_INLINE_HTML).
$htmlPrint = '';
foreach (token_get_all($leer('/resources/views/consulta-notas/criterios-imprimir.php')) as $tk) {
    if (is_array($tk) && $tk[0] === T_INLINE_HTML) { $htmlPrint .= $tk[1]; }
}
$chk('el imprimible de criterios no emite <details>', !str_contains($htmlPrint, '<details'));

$css = $leer('/public/css/app.css');
// `criterio-item__desc` era la lista por competencia, retirada el 24/08 con el
// rediseno de lectura; la clase vigente del cuerpo de la carga es la tabla.
$chk('el SASS nuevo esta compilado en app.css',
    str_contains($css, 'criterios-tabla') && str_contains($css, 'cuadros-kpi__n'));

// ── FASE 7 — render real ────────────────────────────────────────
echo "\nFASE 7 — el tablero compone (render real)\n";
// Se miran los TOKENS de PHP, no el texto: el docblock de la clase menciona la
// palabra SELECT a proposito (documenta que aqui no se escribe ninguno), y un
// grep plano se acusa a si mismo.
$codigo = '';
foreach (token_get_all($leer('/app/Controllers/Admin/CuadrosEstadisticosController.php')) as $t) {
    if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) { continue; }
    $codigo .= is_array($t) ? $t[1] : $t;
}
$chk('CuadrosEstadisticosController no escribe NINGUN SELECT (fuera de comentarios)',
    !preg_match('/\bSELECT\b/i', $codigo));

$anio = new App\Models\AnioAcademicoModel();
$periodos = $anio->query("SELECT p.id, p.numero, p.nombre_display, p.estado, p.anio_id, a.anio
    FROM periodos p INNER JOIN anios_academicos a ON a.id = p.anio_id
    WHERE p.estado IN ('activo','cerrado') ORDER BY a.anio DESC, p.numero ASC");

foreach ($periodos as $p) {
    $pid = (int) $p['id'];
    $aid = (int) $p['anio_id'];
    $etiquetaP = $p['nombre_display'] . ' ' . $p['anio'];

    $conductaModel = new App\Models\ConductaModel();
    $asisModel     = new App\Models\AsistenciaModel();

    $conducta = $conductaModel->getResumenSeccionesPorPeriodo($pid);
    $asis     = $asisModel->getProgresoPorSeccion($pid);

    // ── La evolucion anual NO puede divergir del resumen del bimestre ──
    // getEvolucionAnual() duplica a proposito el SQL del bloque 1 de
    // getResumenBimestre(). Esta asercion es lo que hace segura esa
    // duplicacion: los compara CELDA A CELDA contra datos reales, no contra
    // el texto de la consulta. Si alguien mueve un umbral o el universo en
    // uno solo de los dos, aqui salta.
    $evo     = $anio->getEvolucionAnual($aid);
    $resumen = $anio->getResumenBimestre($pid);

    $celdas = [];
    foreach ($evo['niveles'] as $nv) {
        foreach ($nv['serie'] as $celda) {
            if ((int) $celda['periodo_id'] === $pid) {
                $celdas[(int) $nv['nivel_id']] = $celda;
            }
        }
    }

    $descuadres = [];
    foreach ($resumen['niveles'] as $nv) {
        $celda = $celdas[(int) $nv['nivel_id']] ?? null;
        if ($celda === null) {
            $descuadres[] = $nv['nivel_nombre'] . ': la evolucion no trae este bimestre';
            continue;
        }
        foreach (['ad', 'a', 'b', 'c', 'total_calif'] as $k) {
            if ((int) $celda[$k] !== (int) $nv[$k]) {
                $descuadres[] = $nv['nivel_nombre'] . ".$k: evolucion " . (int) $celda[$k]
                    . ' vs resumen ' . (int) $nv[$k];
            }
        }
    }
    $chk("la evolucion anual cuadra con el resumen de $etiquetaP",
        empty($descuadres),
        $descuadres ? $descuadres[0] : count($resumen['niveles']) . ' nivel(es) contrastado(s)');

    // Frappe Charts exige values.length === labels.length; un hueco desplaza
    // la linea entera SIN dar error. El relleno lo hace el modelo, asi que
    // se comprueba aqui y no en la vista.
    $desparejas = [];
    foreach ($evo['niveles'] as $nv) {
        if (count($nv['serie']) !== count($evo['periodos'])) {
            $desparejas[] = $nv['nivel_nombre'] . ': ' . count($nv['serie'])
                . ' puntos para ' . count($evo['periodos']) . ' bimestres';
        }
    }
    $chk("las series de la evolucion son paralelas al eje X ($etiquetaP)",
        empty($desparejas),
        $desparejas ? $desparejas[0] : count($evo['periodos']) . ' bimestre(s) en el eje');

    $resConducta = ['secciones' => count($conducta), 'cerradas' => 0, 'pend_tutor' => 0, 'pend_auxiliar' => 0, 'esperados' => 0, 'calificados' => 0];
    foreach ($conducta as $f) {
        $resConducta['esperados']   += (int) $f['esperados'];
        $resConducta['calificados'] += (int) $f['calificados'];
        if (!empty($f['tutor_cerrado_en']))      { $resConducta['cerradas']++; }
        elseif (!empty($f['ra_bloqueado_en']))   { $resConducta['pend_tutor']++; }
        else                                     { $resConducta['pend_auxiliar']++; }
    }
    $resAsis = ['secciones' => count($asis), 'completas' => 0, 'esperados' => 0, 'registrados' => 0];
    foreach ($asis as $a) {
        $resAsis['esperados'] += (int) $a['esperados'];
        $resAsis['registrados'] += (int) $a['registrados'];
        if ((int) $a['esperados'] > 0 && (int) $a['registrados'] >= (int) $a['esperados']) { $resAsis['completas']++; }
    }

    $datos = [
        'periodos' => $periodos,
        'periodo'  => $p,
        'bloques'  => [
            'matricula'      => (new App\Models\MatriculaModel())->getResumen($aid),
            'calificaciones' => $resumen,
            'evolucion'      => $evo,
            // Detalle crudo por seccion: el controlador ya lo trae para
            // resumirConducta() y la vista lo grafica sin consultar de nuevo.
            'conducta_secciones' => $conducta,
            'merito'         => $anio->getStatsCierre($pid),
            'empates'        => (new App\Models\OrdenMeritoModel())->gradosConEmpatesPendientes($pid),
            'reaperturas'    => $anio->getReaperturas($pid),
            'conducta'       => $resConducta,
            'asistencia'     => $resAsis,

            // Resultado de conducta y asistencia (27/08/2026). Si estas claves
            // faltaran aqui, las lecturas `??` de las vistas se quedarian sin
            // cubrir y este verificador seguiria verde con la pantalla rota.
            'conducta_literales'   => $conductaModel->getDistribucionLiteralesAnual($aid),
            'conducta_criterios'   => $conductaModel->getIncumplimientoCriterios($pid),
            'asistencia_secciones' => $asisModel->getIncidenciasPorSeccion($pid),
            'asistencia_top'       => $asisModel->getTopIncidenciasPorSeccion($pid),
            'asistencia_evolucion' => $asisModel->getEvolucionIncidenciasAnual($aid),
        ],
    ];

    // Render REAL, capturando cualquier warning/notice como fallo.
    //
    // 🔴 EN SU PROPIO AMBITO, no en el de este script. Con `extract()` + include
    // aqui mismo, cualquier variable de la vista o de sus partials pisa las de
    // este verificador: paso de verdad el 27/08/2026: un `foreach (... as $p)`
    // dentro de un partial se llevo por delante `$p`, la fila del periodo, y el
    // imprimible reventó DESPUÉS con un error que no tenía nada que ver.
    // `Core\View` ya renderiza dentro de un metodo, asi que la aplicacion real
    // nunca corrio ese riesgo; este script sí.
    $render = static function (array $vars, string $vista): string {
        ob_start();
        extract($vars);
        include ROOT_PATH . '/resources/views/admin/cuadros/' . $vista . '.php';
        return (string) ob_get_clean();
    };

    $errores = [];
    set_error_handler(function ($no, $str) use (&$errores) { $errores[] = $str; return true; });
    $html = $render($datos, 'index');
    restore_error_handler();

    $chk("cuadros renderiza $etiquetaP sin avisos",
        empty($errores) && strlen($html) > 2000,
        $errores ? $errores[0] : strlen($html) . ' bytes · conducta ' . $resConducta['cerradas'] . '/' . $resConducta['secciones']
            . ' · asistencia ' . $resAsis['registrados'] . '/' . $resAsis['esperados']);

    // ── Contrato de las pestañas ──────────────────────────────────────
    // Sin esto, una pestaña sin panel (o un panel sin pestaña) deja un bloque
    // entero invisible y NADA falla: la pagina renderiza perfecta y con menos
    // contenido del que deberia.
    preg_match_all('~data-tab="([^"]+)"~', $html, $mTabs);
    preg_match_all('~data-panel="([^"]+)"~', $html, $mPanels);
    $huerfanos = array_merge(
        array_diff($mTabs[1] ?? [], $mPanels[1] ?? []),
        array_diff($mPanels[1] ?? [], $mTabs[1] ?? [])
    );

    // Y cada `aria-controls` tiene que apuntar a un id que exista.
    preg_match_all('~aria-controls="([^"]+)"~', $html, $mCtrl);
    foreach ($mCtrl[1] ?? [] as $idPanel) {
        if (!str_contains($html, 'id="' . $idPanel . '"')) {
            $huerfanos[] = "aria-controls=$idPanel sin destino";
        }
    }

    $chk("las pestañas de $etiquetaP cuadran con sus paneles",
        empty($huerfanos),
        $huerfanos ? implode(', ', $huerfanos) : count($mTabs[1] ?? []) . ' pestaña(s)');

    // Exactamente una activa por grupo, y su panel sin `hidden`: sin JavaScript
    // la pagina tiene que mostrar algo, y con el, tabs.js parte de ese estado.
    $activas = substr_count($html, 'aria-selected="true"');
    $grupos  = substr_count($html, 'role="tablist"');
    $chk("cada grupo de pestañas de $etiquetaP nace con UNA activa",
        $grupos > 0 && $activas === $grupos,
        "$activas activa(s) para $grupos grupo(s)");

    // 🔴 CADA SERIE CALCULADA TIENE QUE TENER SU CONTENEDOR. Es el fallo mas
    // silencioso de esta pantalla: `_chart-data.php` produce el dato, el JSON
    // lo lleva al navegador, cuadros.js registra la fabrica... y no hay ningun
    // <div> donde dibujarlo. No hay error, no hay hueco: el grafico simplemente
    // no existe. Se descubrio asi el 27/08/2026, con la evolucion de conducta
    // desapareciendo del bimestre en curso justo cuando mas sirve.
    preg_match_all('~<div id="(chart-[a-z-]+)"~', $html, $mCharts);
    $contenedores = $mCharts[1] ?? [];

    // clave de $chartData -> id del contenedor que la dibuja.
    $mapaGraficos = [
        'evolucion'          => 'chart-evolucion',
        'brecha'             => 'chart-brecha',
        'conductaEmbudo'     => 'chart-conducta-embudo',
        'conductaSecciones'  => 'chart-conducta-secciones',
        'conductaLiterales'  => 'chart-conducta-literales',
        'conductaEvolucion'  => 'chart-conducta-evolucion',
        'conductaCriterios'  => 'chart-conducta-criterios',
        'asisFaltas'         => 'chart-asis-faltas',
        'asisTardanzas'      => 'chart-asis-tardanzas',
        'asisEvolucion'      => 'chart-asis-evolucion',
        'asisJustificacion'  => 'chart-asis-justificacion',
    ];

    $sinContenedor = [];
    if (preg_match('~<script type="application/json" id="cuadros-data">(.*?)</script>~s', $html, $mTmp)) {
        foreach (array_keys((array) json_decode($mTmp[1], true)) as $clave) {
            $destino = $mapaGraficos[$clave] ?? null;
            if ($destino === null) {
                $sinContenedor[] = "$clave no esta en el mapa del verificador";
            } elseif (!in_array($destino, $contenedores, true)) {
                $sinContenedor[] = "$clave sin <div id=\"$destino\">";
            }
        }
    }

    $chk("cada grafico de $etiquetaP tiene donde dibujarse",
        empty($sinContenedor),
        $sinContenedor ? $sinContenedor[0] : count($contenedores) . ' contenedor(es)');

    // ── Cada seccion lleva SU tabla, con SU encabezado ────────────────
    // Requisito explicito (28/08/2026): con una sola tabla de 180 filas el
    // encabezado de columnas se iba de pantalla al desplazarse y los numeros
    // dejaban de significar nada. Es justo lo que una futura "simplificacion"
    // volveria a juntar, y nada mas lo impediria.
    $nSecciones = count($datos['bloques']['asistencia_top']);
    $nBloques   = substr_count($html, 'class="cuadros-top__bloque"');

    // Una tabla por seccion, y cada una con su <caption> y su <thead>. Ambos se
    // cuentan DENTRO de las tablas de este partial, nunca como clases sueltas
    // por la pagina: desde el 04/09/2026 el listado de "estudiantes en riesgo"
    // reutiliza estas mismas clases base, y un `substr_count` global sumaba sus
    // tablas a estas. El `<table>` de aquel lleva el modificador `--riesgo`, asi
    // que este patron —con la comilla de cierre— no lo captura.
    preg_match_all('~<table class="tabla-notas cuadros-top">(.*?)</table>~s', $html, $mTablas);
    $nTablas     = count($mTablas[1] ?? []);
    $sinCabecera = 0;
    foreach ($mTablas[1] ?? [] as $cuerpo) {
        if (!str_contains($cuerpo, '<caption') || !str_contains($cuerpo, '<thead>')) {
            $sinCabecera++;
        }
    }

    $chk("cada seccion de $etiquetaP tiene su propia tabla con encabezado",
        $nTablas === $nSecciones && $nBloques === $nSecciones && $sinCabecera === 0,
        $sinCabecera > 0
            ? "$sinCabecera tabla(s) sin caption o sin thead"
            : "$nTablas tabla(s) para $nSecciones seccion(es)");

    // ── Estudiantes en riesgo (04/09/2026) ────────────────────────────
    // Mismo contrato que el listado de arriba, una tabla por GRADO. Los
    // modificadores `--riesgo` son los que hacen que estos conteos no se mezclen
    // con los de inasistencias, que usan las mismas clases base.
    $gradosRiesgo = array_values(array_filter(
        $datos['bloques']['merito']['por_grado'],
        static fn(array $g): bool => !empty($g['en_riesgo'])
    ));
    $nRiesgo = count($gradosRiesgo);
    $bRiesgo = substr_count($html, 'cuadros-top__bloque--riesgo');

    // El caption y el thead se comprueban DENTRO de cada tabla de riesgo, no
    // contando clases sueltas por la pagina: asi el aserto no puede cuadrar por
    // casualidad con las tablas del listado de inasistencias.
    preg_match_all(
        '~<table class="tabla-notas cuadros-top cuadros-top--riesgo">(.*?)</table>~s',
        $html, $mRiesgo
    );
    $tRiesgo = count($mRiesgo[1] ?? []);
    $mancas  = 0;
    foreach ($mRiesgo[1] ?? [] as $cuerpo) {
        if (!str_contains($cuerpo, '<caption') || !str_contains($cuerpo, '<thead>')) {
            $mancas++;
        }
    }

    $chk("estudiantes en riesgo de $etiquetaP: una tabla por grado, con encabezado",
        $tRiesgo === $nRiesgo && $bRiesgo === $nRiesgo && $mancas === 0,
        $mancas > 0
            ? "$mancas tabla(s) sin caption o sin thead"
            : "$tRiesgo tabla(s) para $nRiesgo grado(s) con casos");

    // La seccion SIEMPRE esta, con o sin casos: si desaparece cuando nadie llega
    // al umbral, el lector no distingue "no hay nadie en riesgo" de "se rompio".
    $chk("la seccion de riesgo existe en $etiquetaP aunque este vacia",
        str_contains($html, 'id="cuadros-g-riesgo"'),
        $nRiesgo > 0 ? "$nRiesgo grado(s)" : 'sin casos, con estado vacio');

    // Coherencia del dato: todo el que aparece llega al umbral, y el conteo de
    // filas cuadra con lo que devolvio el modelo. Sin esto, un `>=` mal escrito
    // en la vista listaria a gente de mas y las tablas seguirian bien formadas.
    $umbral   = (int) $datos['bloques']['merito']['riesgo_min_c'];
    $bajoUmbral = 0;
    $filas      = 0;
    foreach ($gradosRiesgo as $g) {
        foreach ($g['en_riesgo'] as $al) {
            $filas++;
            if ((int) $al['num_c'] < $umbral) { $bajoUmbral++; }
        }
    }
    $chk("nadie por debajo de $umbral C en la lista de $etiquetaP",
        $bajoUmbral === 0,
        $bajoUmbral > 0 ? "$bajoUmbral fila(s) indebidas" : "$filas fila(s)");

    // ── El JSON que consume cuadros.js ────────────────────────────────
    // Es el unico contrato de la pantalla que PHP no puede romper de forma
    // visible: un desajuste entre labels y values no da error, solo dibuja
    // un grafico corrido. Y con $chartData vacio, PHP serializa [] y el JS
    // encontraria undefined sin que nadie se entere.
    // Se inicializa FUERA del `if`: un bimestre sin datos no emite el tag, y
    // los asertos del A4 de mas abajo lo leen igual. Sin esto, ese caso daba
    // "variable indefinida" justo en el escenario menos probado.
    $nGraficos = 0;

    if (preg_match('~<script type="application/json" id="cuadros-data">(.*?)</script>~s', $html, $mJson)) {
        $payload = json_decode($mJson[1], true);
        $problemas = [];

        if (!is_array($payload)) {
            $problemas[] = 'json_decode fallo: ' . json_last_error_msg();
        } else {
            if (isset($payload['evolucion'])) {
                $nLabels = count($payload['evolucion']['labels']);
                foreach ($payload['evolucion']['datasets'] as $ds) {
                    if (count($ds['values']) !== $nLabels) {
                        $problemas[] = 'evolucion/' . $ds['name'] . ': ' . count($ds['values'])
                            . ' valores para ' . $nLabels . ' etiquetas';
                    }
                }
            }
            $conEjes = [
                ['brecha',             ['mejor', 'peor']],
                ['conductaEmbudo',     ['values']],
                ['conductaSecciones',  ['values']],
                // El eje de criterios lleva ADEMAS el texto completo de cada
                // uno para el tooltip: un eje de codigos C1..C10 no dice nada
                // solo. Si `textos` se descuadra, el tooltip miente.
                ['conductaCriterios',  ['values', 'textos']],
                ['asisFaltas',         ['values']],
                ['asisTardanzas',      ['values']],
            ];
            foreach ($conEjes as [$clave, $ejes]) {
                if (!isset($payload[$clave])) {
                    continue;
                }
                $nLabels = count($payload[$clave]['labels']);
                foreach ($ejes as $eje) {
                    if (count($payload[$clave][$eje]) !== $nLabels) {
                        $problemas[] = "$clave/$eje: " . count($payload[$clave][$eje])
                            . " valores para $nLabels etiquetas";
                    }
                }
            }

            // Las de datasets se comprueban igual que `evolucion`.
            foreach (['conductaLiterales', 'conductaEvolucion', 'asisEvolucion', 'asisJustificacion'] as $clave) {
                if (!isset($payload[$clave])) {
                    continue;
                }
                $nLabels = count($payload[$clave]['labels']);
                foreach ($payload[$clave]['datasets'] as $ds) {
                    if (count($ds['values']) !== $nLabels) {
                        $problemas[] = "$clave/" . ($ds['name'] ?? '?') . ': ' . count($ds['values'])
                            . " valores para $nLabels etiquetas";
                    }
                }
            }
        }

        $chk("el JSON de graficos de $etiquetaP es valido y esta cuadrado",
            empty($problemas),
            $problemas ? $problemas[0] : implode(', ', array_keys($payload)));

        // ── Tabla de valores por grafico (04/09/2026) ─────────────────
        // Gemelo del aserto "cada grafico tiene donde dibujarse", que nacio de
        // un grafico con JSON y sin <div>. Aqui el fallo simetrico: un grafico
        // dibujado cuyos numeros solo existen en el tooltip — invisible en
        // papel, en movil y para quien navega con teclado.
        $nGraficos = count($payload);   // inicializado a 0 mas arriba
        $nTablasV  = substr_count($html, 'class="tabla-notas cuadros-valores__tabla"');
        $chk("cada grafico de $etiquetaP tiene su tabla de valores",
            $nTablasV === $nGraficos,
            "$nTablasV tabla(s) para $nGraficos grafico(s)");

        // Coherencia celda a celda contra el MISMO JSON que dibuja el grafico.
        // Si la tabla se armara por su cuenta, papel y pantalla podrian decir
        // cifras distintas del mismo bimestre sin que nada fallara.
        //
        // Cada tabla se asocia a SU grafico por posicion: es la primera que
        // aparece detras del <div id="chart-…"> correspondiente. Comprobar solo
        // que el numero exista "en alguna parte del HTML" seria un aserto
        // inerte —casi cualquier cifra aparece en una pagina de 300 KB—, que es
        // peor que no tenerlo.
        $idDeClave = [
            'evolucion' => 'chart-evolucion',                'brecha' => 'chart-brecha',
            'conductaEmbudo' => 'chart-conducta-embudo',     'conductaSecciones' => 'chart-conducta-secciones',
            'conductaLiterales' => 'chart-conducta-literales', 'conductaEvolucion' => 'chart-conducta-evolucion',
            'conductaCriterios' => 'chart-conducta-criterios', 'asisFaltas' => 'chart-asis-faltas',
            'asisTardanzas' => 'chart-asis-tardanzas',       'asisEvolucion' => 'chart-asis-evolucion',
            'asisJustificacion' => 'chart-asis-justificacion',
        ];

        $descuadres = [];
        $contrastados = 0;
        foreach ($payload as $clave => $d) {
            $ancla = $idDeClave[$clave] ?? null;
            if ($ancla === null) {
                $descuadres[] = "$clave: grafico sin id conocido en el verificador";
                continue;
            }

            $pos = strpos($html, 'id="' . $ancla . '"');
            if ($pos === false) {
                $descuadres[] = "$clave: no hay <div id=\"$ancla\">";
                continue;
            }

            // Primera tabla de valores por detras de ese div.
            if (!preg_match(
                '~<table class="tabla-notas cuadros-valores__tabla">(.*?)</table>~s',
                substr($html, $pos), $mTabla
            )) {
                $descuadres[] = "$clave: sin tabla de valores detras de su grafico";
                continue;
            }

            // Celdas numericas de esa tabla, en orden de lectura (fila a fila).
            preg_match_all('~<td class="text-center">\s*([^<\s]+)\s*</td>~', $mTabla[1], $mCeldas);
            $enTabla = $mCeldas[1];

            // El JSON va por SERIES (columnas) y la tabla por FILAS: se
            // transpone antes de comparar, o el orden no coincidiria nunca.
            if (isset($d['datasets'])) {
                $series = array_map(static fn($ds) => $ds['values'], $d['datasets']);
            } elseif (isset($d['mejor'])) {
                $series = [$d['mejor'], $d['peor']];
            } else {
                $series = [$d['values'] ?? []];
            }

            $esperado = [];
            foreach (($d['labels'] ?? []) as $i => $_) {
                foreach ($series as $s) {
                    $esperado[] = (string) ($s[$i] ?? '');
                }
            }

            if (count($enTabla) !== count($esperado)) {
                $descuadres[] = "$clave: " . count($enTabla) . ' celdas para '
                    . count($esperado) . ' valores';
                continue;
            }
            foreach ($esperado as $i => $v) {
                if ($enTabla[$i] !== $v) {
                    $descuadres[] = "$clave celda $i: tabla '{$enTabla[$i]}' vs json '$v'";
                    break;
                }
            }
            $contrastados++;
        }
        $chk("los valores tabulados de $etiquetaP salen del mismo JSON",
            empty($descuadres),
            $descuadres ? $descuadres[0] : "$contrastados grafico(s), celda a celda");
    } else {
        // Sin datos no se emite el tag: es correcto, pero que se vea.
        $chk("cuadros omite el JSON de graficos en $etiquetaP (sin datos)", true, 'sin tag cuadros-data');
    }

    // ── La version A4 tambien se renderiza de verdad ──────────────────
    // Comparte datos con la pantalla, pero es OTRA vista: sin esto, una
    // clave que solo ella lea (directorEbr, por ejemplo) reventaria en
    // produccion con el verificador en verde.
    $datosPrint = [
        'titulo'      => 'Cuadros estadisticos',
        'periodo'     => $p,
        'bloques'     => $datos['bloques'],
        'directorEbr' => (new App\Models\DirectorEbrModel())->getVigenteEnFecha($aid),
    ];

    $erroresPrint = [];
    set_error_handler(function ($no, $str) use (&$erroresPrint) { $erroresPrint[] = $str; return true; });
    $htmlPrint = $render($datosPrint, 'imprimir');
    restore_error_handler();

    $chk("el imprimible A4 renderiza $etiquetaP sin avisos",
        empty($erroresPrint) && strlen($htmlPrint) > 2000,
        $erroresPrint ? $erroresPrint[0] : strlen($htmlPrint) . ' bytes'
            . ($datosPrint['directorEbr'] ? ' · con sello' : ' · sin director vigente'));

    // El papel se lee entero de una vez: si heredara las pestañas, dos tercios
    // del informe saldrian en blanco y nadie lo notaria hasta imprimirlo.
    $chk("el imprimible de $etiquetaP no lleva pestañas ni paneles ocultos",
        !str_contains($htmlPrint, 'role="tab"')
            && !str_contains($htmlPrint, 'role="tablist"')
            && !preg_match('~<div[^>]*\shidden~', $htmlPrint),
        'sin role=tab ni hidden');

    // ── Las tablas de valores en el A4 (04/09/2026) ───────────────────
    // 🔴 NI UN `<details>` EN EL PAPEL. Un `<details>` cerrado no imprime su
    // contenido: la tabla saldria en blanco, sin ningun error, y el informe
    // volveria a quedarse sin los numeros que este trabajo vino a poner. Es el
    // mismo motivo por el que el explorador de criterios tiene vista aparte —y
    // ya hay un aserto gemelo para aquel, arriba en este mismo archivo.
    $tablasA4 = substr_count($htmlPrint, 'class="tabla-notas cuadros-valores__tabla"');
    $chk("el imprimible de $etiquetaP trae sus tablas de valores desplegadas",
        !str_contains($htmlPrint, '<details') && $tablasA4 === $nGraficos,
        str_contains($htmlPrint, '<details')
            ? 'hay un <details>: no se imprimiria'
            : "$tablasA4 tabla(s) para $nGraficos grafico(s)");

    // La tabla es HERMANA de `.cuadros-print__chart`, nunca su hija: ese
    // contenedor lleva `page-break-inside: avoid` y con la tabla dentro el
    // bloque entero saltaria de hoja dejando media pagina en blanco.
    $chk("en $etiquetaP ninguna tabla de valores cuelga de un bloque no partible",
        !preg_match(
            '~<div class="cuadros-print__chart">(?:(?!</div>).)*cuadros-valores__tabla~s',
            $htmlPrint
        ),
        'todas fuera de .cuadros-print__chart');

    // Los KPIs que la pantalla mostraba y el papel no. "Esperan al tutor" y
    // "Esperan al auxiliar" llegaban al A4 SOLO por la leyenda del grafico de
    // embudo, que no se registra cuando la suma es cero: el informe podia
    // quedarse sin decir a quien esta esperando el cierre.
    $kpisPapel = ['Esperan al tutor', 'Esperan al auxiliar'];
    $faltan = array_values(array_filter(
        $kpisPapel,
        static fn(string $k): bool => !str_contains($htmlPrint, $k)
    ));
    $chk("el imprimible de $etiquetaP trae los KPIs de proceso de conducta",
        empty($faltan),
        $faltan ? 'falta: ' . $faltan[0] : implode(' · ', $kpisPapel));

    // Cada grafico impreso lleva su nota de lectura: en papel nadie puede
    // preguntar que significa lo que esta viendo.
    $chk("cada grafico impreso de $etiquetaP lleva su nota de lectura",
        substr_count($htmlPrint, 'cuadros-print__nota') === $nGraficos,
        substr_count($htmlPrint, 'cuadros-print__nota') . " nota(s) para $nGraficos grafico(s)");

    // ── Coherencia de la distribucion de conducta ─────────────────────
    // Gemelo del aserto que ya compara getEvolucionAnual con getResumenBimestre:
    // los estudiantes con literal compuesto no pueden pasar de los calificados
    // que cuenta el panel del director, que mira la MISMA grilla desde otra
    // consulta. Es <= y no ==: `calificados` exige las 10 respuestas completas,
    // mientras que el I Bimestre (legado) tiene literal sin ninguna respuesta.
    $litTotal = 0;
    foreach ($datos['bloques']['conducta_literales']['niveles'] as $nv) {
        foreach ($nv['serie'] as $celda) {
            if ((int) $celda['periodo_id'] === $pid) {
                $litTotal += (int) $celda['total'];
            }
        }
    }
    $esLegado = empty($datos['bloques']['conducta_criterios']['criterios']);
    $chk("la distribucion de conducta de $etiquetaP cuadra con el avance",
        $esLegado || $litTotal <= (int) $resConducta['esperados'],
        $litTotal . ' con literal de ' . $resConducta['esperados'] . ' esperados'
            . ($esLegado ? ' (bimestre legado)' : ''));

    // ── El literal NO se recalcula por un camino paralelo ─────────────
    // ESTE es el aserto que prueba que `getDistribucionLiteralesAnual` pasa por
    // `componerLiteral()` y no por una copia nueva de la aritmetica.
    //
    // Se contrasta contra `getEstudiantesParaTutor`, que es la OTRA copia de esa
    // aritmetica que ya vive en el repositorio (calcula `literal_final` en PHP,
    // alumno a alumno, para la grilla del tutor). Comparar el agregado nuevo
    // contra ella cubre las dos a la vez: si cualquiera de las tres —el helper,
    // la grilla o el tablero— se desvia, los conteos dejan de cuadrar.
    //
    // El universo tiene que ser identico: mismo roster, mismo periodo, sin mirar
    // el cierre. Por eso se comparan literal a literal y no solo el total.
    $porGrilla  = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];
    $porTablero = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];

    foreach ($conducta as $sec) {
        $totalCrit = $conductaModel->totalCriterios((int) $sec['nivel_id']);
        foreach ($conductaModel->getEstudiantesParaTutor((int) $sec['seccion_id'], $pid, $totalCrit) as $al) {
            if (!empty($al['literal_final'])) {
                $porGrilla[$al['literal_final']]++;
            }
        }
    }

    foreach ($datos['bloques']['conducta_literales']['niveles'] as $nv) {
        foreach ($nv['serie'] as $celda) {
            if ((int) $celda['periodo_id'] !== $pid) {
                continue;
            }
            foreach (['AD' => 'ad', 'A' => 'a', 'B' => 'b', 'C' => 'c'] as $lit => $campo) {
                $porTablero[$lit] += (int) $celda[$campo];
            }
        }
    }

    $fmt = static fn(array $x): string => "AD{$x['AD']} A{$x['A']} B{$x['B']} C{$x['C']}";
    $chk("el literal de conducta de $etiquetaP sale de un solo sitio",
        $porGrilla === $porTablero,
        $porGrilla === $porTablero
            ? 'tablero y grilla del tutor coinciden: ' . $fmt($porTablero)
            : 'tablero ' . $fmt($porTablero) . ' != grilla ' . $fmt($porGrilla));
}

echo "\n", $ok ? "== FASES 4-7 EN VERDE ==\n" : "== HAY FALLOS ==\n";
exit($ok ? 0 : 1);
