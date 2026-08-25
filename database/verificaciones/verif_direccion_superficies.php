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

// ── FASE 6 ───────────────────────────────────────────────────────
echo "\nFASE 6 — superficies nuevas de consulta\n";
$rutas = $leer('/routes/web.php');
foreach ([
    '/consulta-notas/{periodo_id}/seccion/{seccion_id}/asistencia',
    '/consulta-notas/{periodo_id}/docentes',
    '/consulta-notas/{periodo_id}/docente/{docente_id}',
    '/director/cargas/seccion/{seccion_id}/horario',
    '/admin/cuadros',
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

    $conducta = (new App\Models\ConductaModel())->getResumenSeccionesPorPeriodo($pid);
    $asis     = (new App\Models\AsistenciaModel())->getProgresoPorSeccion($pid);

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
            'calificaciones' => $anio->getResumenBimestre($pid),
            'merito'         => $anio->getStatsCierre($pid),
            'empates'        => (new App\Models\OrdenMeritoModel())->gradosConEmpatesPendientes($pid),
            'reaperturas'    => $anio->getReaperturas($pid),
            'conducta'       => $resConducta,
            'asistencia'     => $resAsis,
        ],
    ];

    // Render REAL, capturando cualquier warning/notice como fallo.
    $etiquetaP = $p['nombre_display'] . ' ' . $p['anio'];
    $errores = [];
    set_error_handler(function ($no, $str) use (&$errores) { $errores[] = $str; return true; });
    ob_start();
    extract($datos);
    include ROOT_PATH . '/resources/views/admin/cuadros/index.php';
    $html = ob_get_clean();
    restore_error_handler();

    $chk("cuadros renderiza $etiquetaP sin avisos",
        empty($errores) && strlen($html) > 2000,
        $errores ? $errores[0] : strlen($html) . ' bytes · conducta ' . $resConducta['cerradas'] . '/' . $resConducta['secciones']
            . ' · asistencia ' . $resAsis['registrados'] . '/' . $resAsis['esperados']);
}

echo "\n", $ok ? "== FASES 4-7 EN VERDE ==\n" : "== HAY FALLOS ==\n";
exit($ok ? 0 : 1);
